<?php

namespace App\Http\Controllers;

use App\Models\company;
use App\Scopes\AuthCompanyScope;
use App\Support\CompanyScope;
use App\Models\department;
use App\Models\Employee;
use App\Models\location;
use App\Models\Team;
use App\Support\ManagedEmployeeScope;
use App\Models\EmployeeActivityLog;
use App\Models\leave;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

//Notification
use App\Notifications\LeaveNotification; //Database
use App\Services\LeaveNotifier;
use App\Models\User;
use App\Models\EmployeeLeaveTypeDetail;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class LeaveController extends Controller
{
    private function logEmployeeActivity(int $employeeId, string $action, string $description, array $meta = []): void
    {
        EmployeeActivityLog::create([
            'employee_id' => $employeeId,
            'performed_by' => optional(auth()->user())->id,
            'action' => $action,
            'description' => $description,
            'meta' => empty($meta) ? null : $meta,
            'ip_address' => request()->ip(),
        ]);
    }

    protected function isTeamLeaveManager(): bool
    {
        return (int) auth()->user()->role_users_id !== 1
            && \App\Models\Project::userLeadsAnyProject((int) auth()->id());
    }

    protected function isLocationLeaveManager(): bool
    {
        return (int) auth()->user()->role_users_id !== 1
            && location::userCanManageLocationLeaveRequests((int) auth()->id());
    }

    protected function isOrgDepartmentManager(): bool
    {
        return department::where('department_head', auth()->id())->exists();
    }

    protected function teamMemberIdsForLeaveManagement(): array
    {
        return \App\Models\Project::memberEmployeeIdsLedBy((int) auth()->id());
    }

    protected function canAccessLeaveModule(): bool
    {
        if (auth()->user()->can('view-leave')) {
            return true;
        }

        if ($this->isOrgDepartmentManager()) {
            return true;
        }

        return ManagedEmployeeScope::canManageScopedLeave((int) auth()->id());
    }

    protected function canViewLeaveRecord(leave $leave): bool
    {
        if (auth()->user()->can('view-leave')) {
            return true;
        }

        if ($this->isOrgDepartmentManager()) {
            $managedIds = department::where('department_head', auth()->id())->pluck('id');

            return $managedIds->contains((int) $leave->department_id);
        }

        if (ManagedEmployeeScope::canManageScopedLeave((int) auth()->id())) {
            return in_array(
                (int) $leave->employee_id,
                ManagedEmployeeScope::managedEmployeeIds((int) auth()->id()),
                true
            );
        }

        return false;
    }

    protected function canManageLeaveRecord(leave $leave): bool
    {
        if (auth()->user()->can('edit-leave')) {
            return true;
        }

        $leave->loadMissing('department:id,department_head');

        if ($this->isOrgDepartmentManager()
            && (int) ($leave->department?->department_head ?? 0) === (int) auth()->id()) {
            return true;
        }

        if (ManagedEmployeeScope::canManageScopedLeave((int) auth()->id())) {
            return in_array(
                (int) $leave->employee_id,
                ManagedEmployeeScope::managedEmployeeIds((int) auth()->id()),
                true
            );
        }

        return false;
    }

    protected function applyLeaveListScopeForCurrentUser($query): void
    {
        if (auth()->user()->can('view-leave')) {
            return;
        }

        if ($this->isOrgDepartmentManager()) {
            $managedDepartmentIds = department::where('department_head', auth()->id())->pluck('id');
            $query->whereIn('department_id', $managedDepartmentIds);

            return;
        }

        $memberIds = ManagedEmployeeScope::managedEmployeeIds((int) auth()->id());

        if ($memberIds === []) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('employee_id', $memberIds);
        }
    }

    public function index()
    {
        $logged_user = auth()->user();
        $isLocationHead = location::userIsLocationHead((int) $logged_user->id);
        $companies = $isLocationHead && ! $logged_user->can('view-leave')
            ? CompanyScope::companiesForLocationHead((int) $logged_user->id)
            : CompanyScope::companiesForSelect();
        $leave_types = LeaveType::select('id', 'leave_type', 'allocated_day')->get();
        $teamLeaveManagerViewOnly = ManagedEmployeeScope::canManageLeaveRequests((int) $logged_user->id)
            && ! $logged_user->can('view-leave')
            && ! $this->isOrgDepartmentManager();
        $wfhOnly = request()->boolean('wfh');

        if ($this->canAccessLeaveModule()) {
            if (request()->ajax()) {
                $leaveQuery = $teamLeaveManagerViewOnly
                    ? leave::withoutGlobalScope(AuthCompanyScope::class)
                    : leave::query();
                $leaveQuery = $leaveQuery
                    ->with([
                        'employee:id,first_name,last_name',
                        'department:id,department_name,department_head',
                        'LeaveType:id,leave_type',
                        'approvedByUser:id,first_name,last_name',
                        'approvedByEmployee:id,first_name,last_name',
                    ])
                    ->orderByDesc('id');

                $this->applyLeaveListScopeForCurrentUser($leaveQuery);

                if ($wfhOnly) {
                    $leaveQuery->whereHas('LeaveType', function ($query) {
                        $query->where('leave_type', 'like', '%wfh%')
                            ->orWhere('leave_type', 'like', '%work from home%');
                    });
                } else {
                    $leaveQuery->where(function ($query) {
                        $query->whereDoesntHave('LeaveType', function ($wfhQuery) {
                            $wfhQuery->where('leave_type', 'like', '%wfh%')
                                ->orWhere('leave_type', 'like', '%work from home%');
                        })->orWhereNull('leave_type_id');
                    });
                }

                return datatables()->of($leaveQuery)
                    ->setRowId(function ($row) {
                        return $row->id;
                    })
                    ->addColumn('leave_type', function ($row) {
                        return $row->LeaveType->leave_type ?? '';
                    })
                    ->addColumn('department', function ($row) {
                        return $row->department->department_name ?? '';
                    })
                    ->addColumn('employee', function ($row) {
                        return $row->employee->full_name ?? '';
                    })
                    ->addColumn('approved_by_name', function ($row) {
                        return $row->approvedByName();
                    })
                    ->addColumn('action', function ($data) {
                        $button = '<button type="button" name="show" id="' . $data->id . '" class="show_new btn btn-success btn-sm"><i class="dripicons-preview"></i></button>';

                        if (($data->status ?? '') !== 'pending') {
                            return $button;
                        }

                        if ($this->canManageLeaveRecord($data)) {
                            $button .= '&nbsp;&nbsp;';
                            $button .= '<button type="button" name="approve" id="' . $data->id . '" class="approve-leave btn btn-primary btn-sm">'.__('Approve').'</button>';
                            $button .= '&nbsp;&nbsp;';
                            $button .= '<button type="button" name="reject" id="' . $data->id . '" class="reject-leave btn btn-danger btn-sm">'.__('Reject').'</button>';
                        }

                        return $button;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            return view('timesheet.leave.index', compact('companies', 'leave_types', 'wfhOnly', 'teamLeaveManagerViewOnly'));
        }

        return abort(403, __('You are not authorized'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->can('store-leave') || auth()->user()) {
            $validator = Validator::make(
                $request->only('leave_type', 'company_id', 'department_id', 'employee_id', 'start_date', 'end_date', 'status'),
                [
                    'company_id' => 'required',
                    'department_id' => 'required',
                    'employee_id' => 'required',
                    'leave_type' => 'required',
                    'status' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required|after_or_equal:start_date'
                ]
            );


            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()->all()]);
            }


            $currentDate = new DateTime();
            $requestStartDate = new DateTime($request->start_date);
            $requestEndDate = new DateTime($request->end_date);

            if ($requestStartDate < $currentDate->setTime(0, 0, 0, 0)) {
                throw new Exception('The start date is less than the current date');
            } else if ($requestEndDate < $currentDate->setTime(0, 0, 0, 0)) {
                throw new Exception('The end date is less than the current date');
            }


            try {
                if ((int) $request->leave_type === 0) {
                    $fallbackWfhType = LeaveType::firstOrCreate(
                        ['leave_type' => 'WFH'],
                        ['allocated_day' => 365, 'company_id' => null]
                    );
                    $request->merge(['leave_type' => $fallbackWfhType->id]);
                }

                $leave = LeaveType::findOrFail($request->leave_type);
                $data = [];
                $data['employee_id'] = $request->employee_id;
                $data['company_id'] = $request->company_id;
                $data['department_id'] = $request->department_id;
                $data['leave_type_id'] = $request->leave_type;
                $data['leave_reason'] = $request->leave_reason;
                $data['remarks'] = $request->remarks;
                $data['status'] = $request->status;
                $data['is_notify'] = $request->is_notify;
                $data['start_date'] = $request->start_date;
                $data['end_date'] = $request->end_date;
                $data['total_days'] = $request->diff_date_hidden;

                $isWfhLeave = $this->isWfhLeaveTypeId((int) $request->leave_type);
                if ($isWfhLeave) {
                    $data['status'] = 'pending';
                    $data['hr_approval_status'] = 'pending';
                    $data['manager_approval_status'] = 'pending';
                }

                if (($data['status'] ?? $request->status) == 'approved') {
                    try {
                        $this->employeeLeaveTypeDataManage(null, $request, $request->employee_id, false);
                    } catch (Exception $e) {
                        return response()->json(['error' => $e->getMessage()]);
                    }
                }

                $leave = leave::create($data);
                $leave->refresh();
                $leave->load(['employee', 'LeaveType', 'company', 'department']);

                $this->logEmployeeActivity((int) $leave->employee_id, $isWfhLeave ? 'wfh.requested' : 'leave.requested', $isWfhLeave ? 'WFH request submitted.' : 'Leave request submitted.', [
                    'leave_id' => $leave->id,
                    'leave_type_id' => $leave->leave_type_id,
                    'status' => $leave->status,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'total_days' => $leave->total_days,
                ]);

                try {
                    if ($isWfhLeave) {
                        LeaveNotifier::notifyWfh($leave, 'requested');
                    } else {
                        LeaveNotifier::notify($leave, 'requested');
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Leave/WFH submit notification failed', [
                        'leave_id' => $leave->id,
                        'employee_id' => $leave->employee_id,
                        'is_wfh' => $isWfhLeave,
                        'message' => $e->getMessage(),
                    ]);
                }

            }
            catch (Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }

            return response()->json(['success' => __('Data Added successfully.')]);
        }
        return response()->json(['success' => __('You are not authorized')]);
    }

    public function show($id)
    {
        if (request()->ajax()) {
            $data = leave::with(['department:id,department_head', 'approvedByUser:id,first_name,last_name'])->findOrFail($id);

            if (! $this->canViewLeaveRecord($data)) {
                return response()->json(['error' => __('You are not authorized')], 403);
            }
            $company_name = $data->company->company_name ?? '';
            $employee_name = $data->employee->full_name;
            $department = $data->department->department_name ?? '';
            $leave_type_name = $data->LeaveType->leave_type ?? '';

            $start_date_name = $data->start_date;
            $end_date_name = $data->end_date;


            return response()->json([
                'data' => $data,
                'employee_name' => $employee_name,
                'company_name' => $company_name,
                'department' => $department,
                'leave_type_name' => $leave_type_name,
                'start_date_name' => $start_date_name,
                'end_date_name' => $end_date_name,
                'approved_by_name' => $data->approvedByName() ?: '-',
            ]);
        }
    }

    public function edit($id)
    {
        if (request()->ajax()) {
            $data = leave::with('department:id,department_head')->findOrFail($id);

            if (! $this->canViewLeaveRecord($data)) {
                return response()->json(['error' => __('You are not authorized')], 403);
            }

            $leaveStartDate = date('Y-m-d', strtotime($data->start_date));

            $departments = department::select('id', 'department_name')
                ->where('company_id', $data->company_id)->get();

            $employees = Employee::select('id', 'first_name', 'last_name')->where('department_id', $data->department_id)->where('is_active', 1)->where('exit_date', NULL)->get();

            return response()->json(['data' => $data, 'employees' => $employees, 'departments' => $departments, 'leaveStartDate' => $leaveStartDate]);
        }
    }

    public function update(Request $request)
    {
        $id = $request->hidden_id;
        $leaveForPermission = leave::with('department:id,department_head')->find($id);

        if (! $leaveForPermission || ! $this->canManageLeaveRecord($leaveForPermission)) {
            return response()->json(['success' => __('You are not authorized')]);
        }

        $validator = Validator::make(
                $request->only(
                    'leave_type',
                    'company_id',
                    'department_id',
                    'employee_id',
                    'start_date',
                    'end_date',
                    'leave_reason',
                    'remarks',
                    'status',
                    'is_notify',
                    'diff_date_hidden',
                    'leave_type_hidden',
                    'employee_id_hidden'
                ),
                [
                    'company_id' => 'required',
                    'department_id' => 'required',
                    'employee_id' => 'required',
                    'leave_type' => 'required',
                    'status' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required|after_or_equal:start_date',
                    'diff_date_hidden' => 'nullable|numeric'
                ]
            );

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()->all()]);
            }


            $data = [];
            global $employee_id;
            $data['leave_reason'] = $request->leave_reason;
            $data['remarks'] = $request->remarks;
            $data['is_notify'] = $request->is_notify;
            $data['start_date'] = $request->start_date;
            $data['end_date'] = $request->end_date;


            if ($request->diff_date_hidden != null) {
                $data['total_days'] = $request->diff_date_hidden;
            }
            if ($request->employee_id) {
                $employee_id = $request->employee_id;
                $data['employee_id'] = $employee_id;
            } else {
                $employee_id = $request->employee_id_hidden;
            }

            if ($request->company_id) {
                $data['company_id'] = $request->company_id;
            }

            if ($request->department_id) {
                $data['department_id'] = $request->department_id;
            }
            if ($request->status) {
                $data['status'] = $request->status;
            }

            $leave = leave::find($id);
            $isWfhLeave = $this->isWfhLeaveTypeId((int) ($request->leave_type ?: $leave->leave_type_id));
            $previousManagerStatus = $leave->manager_approval_status;
            $previousStatus = $leave->status;

            if ($isWfhLeave) {
                $this->applyWfhApprovalStatus($leave, $data, $request);
            }

            $this->applyLeaveDecisionMeta($data, $request->status, $previousStatus);

            //Employee Remaining Leave Manage
            $isEmplyoeeRemaingLeaveRestore = null;
            if (
                ! $isWfhLeave &&
                $leave->status == 'approved' &&
                ($request->status == 'pending' || $request->status == 'rejected')
            ) {
                $isEmplyoeeRemaingLeaveRestore = true;
            } else if (
                ! $isWfhLeave &&
                ($leave->status == 'pending' || $leave->status == 'rejected') &&
                $request->status === 'approved'
            ) {
                $isEmplyoeeRemaingLeaveRestore = false;
            }

            try {
                $shouldManageLeaveQuota = ! $isWfhLeave && $request->status !== null;
                if ($shouldManageLeaveQuota) {
                    $this->employeeLeaveTypeDataManage($leave, $request, $employee_id, $isEmplyoeeRemaingLeaveRestore);
                }

                $leave->update($data);
                $leave->refresh();

                $this->logEmployeeActivity((int) $leave->employee_id, $isWfhLeave ? 'wfh.updated' : 'leave.updated', $isWfhLeave ? 'WFH request updated.' : 'Leave request updated.', [
                    'leave_id' => $leave->id,
                    'leave_type_id' => $leave->leave_type_id,
                    'status' => $leave->status,
                    'manager_approval_status' => $leave->manager_approval_status,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                ]);

                if ($isWfhLeave) {
                    // Direct update on manager approval (no dependency on sync timing).
                    if (($leave->manager_approval_status ?? 'pending') === 'approved' && $leave->status === 'approved') {
                        Employee::where('id', $leave->employee_id)->update(['attendance_type' => 'general']);
                    } else {
                        // For rejected/pending/date changes, fall back to calculated sync.
                        $this->syncEmployeeAttendanceTypeForWfh((int) $leave->employee_id);
                    }

                    if (
                        $leave->manager_approval_status !== $previousManagerStatus ||
                        $leave->status !== $previousStatus
                    ) {
                        if ($leave->status === 'approved') {
                            LeaveNotifier::notifyWfh($leave, 'approved');
                        } elseif ($leave->status === 'rejected') {
                            LeaveNotifier::notifyWfh($leave, 'rejected');
                        } else {
                            LeaveNotifier::notifyWfh($leave, 'pending');
                        }
                    }
                } elseif ($leave->status !== $previousStatus) {
                    try {
                        if ($leave->status === 'approved') {
                            LeaveNotifier::notify($leave, 'approved');
                        } elseif ($leave->status === 'rejected') {
                            LeaveNotifier::notify($leave, 'rejected');
                        } else {
                            LeaveNotifier::notify($leave, 'pending');
                        }
                    } catch (\Throwable $e) {
                        // Do not block status update on notification failures.
                    }
                }

                if ($data['is_notify'] != NULL) {
                    $text = "A leave-notification has been updated";
                    $notifiable = User::findOrFail($data['employee_id']);
                    $notifiable->notify(new LeaveNotification($text)); //To Employee
                }
            }
            catch (Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }
        return response()->json(['success' => __('Data is successfully updated')]);
    }

    public function decision(Request $request, $id)
    {
        $status = strtolower((string) $request->status);
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return response()->json(['error' => __('Invalid status')]);
        }

        $leave = leave::findOrFail($id);

        if (strtolower((string) $leave->status) === $status) {
            return response()->json(['success' => __('Data is successfully updated')]);
        }

        $proxyRequest = new Request([
            'hidden_id' => $leave->id,
            'leave_type' => $leave->leave_type_id,
            'company_id' => $leave->company_id,
            'department_id' => $leave->department_id,
            'employee_id' => $leave->employee_id,
            'start_date' => $leave->start_date,
            'end_date' => $leave->end_date,
            'leave_reason' => $leave->leave_reason,
            'remarks' => $leave->remarks,
            'status' => $status,
            'is_notify' => $leave->is_notify,
            'diff_date_hidden' => $leave->total_days,
            'leave_type_hidden' => $leave->leave_type_id,
            'employee_id_hidden' => $leave->employee_id,
        ]);

        return $this->update($proxyRequest);
    }


    private function applyLeaveDecisionMeta(array &$data, ?string $newStatus, ?string $previousStatus): void
    {
        $newStatus = strtolower((string) ($newStatus ?? ''));
        $previousStatus = strtolower((string) ($previousStatus ?? ''));

        if (! in_array($newStatus, ['approved', 'rejected'], true) || $newStatus === $previousStatus) {
            return;
        }

        $data['approved_by'] = auth()->id();
    }

    private function isWfhLeaveTypeId(int $leaveTypeId): bool
    {
        if (! $leaveTypeId) {
            return false;
        }

        $leaveType = LeaveType::find($leaveTypeId);

        if (! $leaveType || ! isset($leaveType->leave_type)) {
            return false;
        }

        $name = strtolower((string) $leaveType->leave_type);
        return strpos($name, 'wfh') !== false || strpos($name, 'work from home') !== false;
    }

  private function applyWfhApprovalStatus(leave $leave, array &$data, Request $request): void
{
    $requestStatus = strtolower((string) $request->status);

    if (! in_array($requestStatus, ['approved', 'rejected', 'pending'], true)) {
        $requestStatus = 'pending';
    }

    $data['manager_approval_status'] = $requestStatus;
    $data['status'] = $requestStatus;
}

    private function isHrUser(): bool
    {
        $role = Role::find(auth()->user()->role_users_id);
        $roleName = strtolower((string) ($role->name ?? ''));
        return strpos($roleName, 'hr') !== false || strpos($roleName, 'human') !== false;
    }

    private function syncEmployeeAttendanceTypeForWfh(int $employeeId): void
    {
        $today = now()->toDateString();
        $hasActiveWfh = leave::query()
            ->join('leave_types', 'leave_types.id', '=', 'leaves.leave_type_id')
            ->where('leaves.employee_id', $employeeId)
            ->where('leaves.status', 'approved')
            ->where('leaves.manager_approval_status', 'approved')
            ->whereDate('leaves.start_date', '<=', $today)
            ->whereDate('leaves.end_date', '>=', $today)
            ->where(function ($query) {
                $query->where('leave_types.leave_type', 'like', '%wfh%')
                    ->orWhere('leave_types.leave_type', 'like', '%work from home%');
            })
            ->exists();

        Employee::where('id', $employeeId)->update([
            'attendance_type' => $hasActiveWfh ? 'general' : 'location_based',
        ]);
    }

    private function employeeLeaveTypeDataManage($leave, $request, $employee_id, $isRestore)
    {
        if ($leave) {
            $currentDate = new DateTime();

            $previousStartDate = new DateTime($leave->start_date);
            $previousEndDate = new DateTime($leave->end_date);

            $requestStartDate = new DateTime($request->start_date);
            $requestEndDate = new DateTime($request->end_date);

            $isStartDateChange = true;
            $isEndDateChange = true;

            if ($previousStartDate == $requestStartDate) {
                $isStartDateChange = false;
            }
            if ($previousEndDate == $requestEndDate) {
                $isEndDateChange = false;
            }

            if ($requestStartDate < $currentDate->setTime(0, 0, 0, 0) && $isStartDateChange) {
                throw new Exception('The start date is less than the current date');
            }
            if ($requestEndDate < $currentDate->setTime(0, 0, 0, 0) && $isEndDateChange) {
                throw new Exception('The end date is less than the current date');
            }
        }



        $employeeLeaveTypeDetail = EmployeeLeaveTypeDetail::where('employee_id', $employee_id)->first();
        $dataLeaveType = [];
        if ($employeeLeaveTypeDetail) {
            $leaveTypeUnserialize = unserialize($employeeLeaveTypeDetail->leave_type_detail);

            //Find the specific leave type from the serilize data from database & compare
            foreach ($leaveTypeUnserialize as $key => $itemArr) {
                if (in_array($request->leave_type, $itemArr)) { //leave_type = leave_type_id
                    if ($request->diff_date_hidden > $itemArr['remaining_allocated_day']) {
                        throw new Exception('Allocated quota for this leave type is less then requested total days');
                    }
                    if ($isRestore === true) {
                        $dataLeaveType[$key]['remaining_allocated_day'] = $itemArr['remaining_allocated_day'] + $request->diff_date_hidden;
                    } else if ($isRestore === false) {
                        $dataLeaveType[$key]['remaining_allocated_day'] = $itemArr['remaining_allocated_day'] - $request->diff_date_hidden;
                    } else {
                        $dataLeaveType[$key]['remaining_allocated_day'] = $itemArr['remaining_allocated_day'];
                    }
                } else {
                    $dataLeaveType[$key]['remaining_allocated_day'] = $itemArr['remaining_allocated_day'];
                }
                $dataLeaveType[$key]['leave_type_id'] = $itemArr['leave_type_id'];
                $dataLeaveType[$key]['leave_type'] = $itemArr['leave_type'];
                $dataLeaveType[$key]['allocated_day'] = $itemArr['allocated_day'];
            }
        }

        if (!empty($dataLeaveType)) {
            EmployeeLeaveTypeDetail::updateOrCreate(
                ['employee_id' => $employee_id],
                ['leave_type_detail' => serialize($dataLeaveType)]
            );
        }
    }




    public function destroy($id)
    {
        if (!env('USER_VERIFIED')) {
            return response()->json(['error' => 'This feature is disabled for demo!']);
        }
        $logged_user = auth()->user();

        if ($logged_user->can('delete-leave')) {
            leave::whereId($id)->delete();

            return response()->json(['success' => __('Data is successfully deleted')]);
        }
        return response()->json(['success' => __('You are not authorized')]);
    }






    public function delete_by_selection(Request $request)
    {
        if (!env('USER_VERIFIED')) {
            return response()->json(['error' => 'This feature is disabled for demo!']);
        }
        $logged_user = auth()->user();

        if ($logged_user->can('delete-leave')) {

            $leave_id = $request['leaveIdArray'];
            $leave = leave::whereIntegerInRaw('id', $leave_id);
            if ($leave->delete()) {
                return response()->json(['success' => __('Multi Delete', ['key' => trans('file.Leave')])]);
            } else {
                return response()->json(['error' => 'Error, selected leaves can not be deleted']);
            }
        }

        return response()->json(['success' => __('You are not authorized')]);
    }

    public function calendarableDetails($id)
    {
        if (request()->ajax()) {
            $data = Leave::with(
                'company:id,company_name',
                'LeaveType:id,leave_type',
                'employee:id,first_name,last_name'
            )->findOrFail($id);

            $new = [];

            $new['Company'] = $data->company->company_name;
            $new['Employee'] = $data->employee->full_name;
            $new['Arrangement Type'] = $data->LeaveType->leave_type;
            $new['Start Date'] = $data->start_date;
            $new['End Date'] = $data->end_date;
            $new['Leave Reason'] = $data->leave_reason;
            $new['Remarks'] = $data->remarks;
            $new['Status'] = 'Approved';

            return response()->json(['data' => $new]);
        }
    }
}
