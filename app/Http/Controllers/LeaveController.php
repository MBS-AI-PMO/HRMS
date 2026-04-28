<?php

namespace App\Http\Controllers;

use App\Models\company;
use App\Models\department;
use App\Models\Employee;
use App\Models\EmployeeActivityLog;
use App\Models\leave;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

//Notification
use App\Notifications\EmployeeLeaveNotification; //Mail
use App\Notifications\LeaveNotification; //Database
use App\Notifications\LeaveNotificationToAdmin; //Database
use App\Notifications\WfhRequestNotificationToApprover; //Database
use App\Notifications\WfhEventNotification; //Database+Mail
use App\Models\User;
use App\Models\EmployeeLeaveTypeDetail;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Notification;
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

    public function index()
    {
        $logged_user = auth()->user();
        $companies = company::select('id', 'company_name')->get();
        $leave_types = LeaveType::select('id', 'leave_type', 'allocated_day')->get();
        $managedDepartmentIds = department::where('department_head', $logged_user->id)->pluck('id');
        $isDepartmentManager = $managedDepartmentIds->isNotEmpty();
        $canViewLeaveModule = $logged_user->can('view-leave') || $isDepartmentManager;
        $wfhOnly = request()->boolean('wfh');

        if ($canViewLeaveModule) {
            if (request()->ajax()) {
                $leaveQuery = leave::query()
                    ->with([
                        'employee:id,first_name,last_name',
                        'department:id,department_name,department_head',
                        'LeaveType:id,leave_type',
                    ])
                    ->orderByDesc('id');

                if (!$logged_user->can('view-leave') && $isDepartmentManager) {
                    $leaveQuery->whereIn('department_id', $managedDepartmentIds);
                }

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
                    ->addColumn('action', function ($data) {
                        $button = '<button type="button" name="show" id="' . $data->id . '" class="show_new btn btn-success btn-sm"><i class="dripicons-preview"></i></button>';

                        if (($data->status ?? '') !== 'pending') {
                            return $button;
                        }

                        $isDepartmentManager = (int) ($data->department->department_head ?? 0) === (int) auth()->id();
                        $canManagePending = auth()->user()->can('edit-leave') || $isDepartmentManager;

                        if ($canManagePending) {
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

            return view('timesheet.leave.index', compact('companies', 'leave_types', 'wfhOnly'));
        }

        return abort('403', __('You are not authorized'));
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

                $this->logEmployeeActivity((int) $leave->employee_id, $isWfhLeave ? 'wfh.requested' : 'leave.requested', $isWfhLeave ? 'WFH request submitted.' : 'Leave request submitted.', [
                    'leave_id' => $leave->id,
                    'leave_type_id' => $leave->leave_type_id,
                    'status' => $leave->status,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'total_days' => $leave->total_days,
                ]);

                if ($isWfhLeave) {
                    $this->notifyWfhEvent($leave, 'requested');
                } elseif ($leave->is_notify == 1) {
                    $text = "A new leave-notification has been published";
                    $notifiable = User::findOrFail($data['employee_id']);
                    $notifiable->notify(new LeaveNotification($text)); //To Employee
                } elseif ((Auth::user()->role_users_id != 1) && ($leave->is_notify == NULL)) {
                    //get-leave-notification - 294
                    $role_ids = DB::table('role_has_permissions')->where('permission_id', 294)->get()->pluck('role_id');
                    $role_ids[] = 1;

                    $notifiable = User::whereIn('role_users_id', $role_ids)->get();
                    foreach ($notifiable as $item) {
                        $item->notify(new LeaveNotificationToAdmin());
                    }

                    //Mail
                    $department = department::with('DepartmentHead:id,email')->where('id', $request->department_id)->first();
                    if(isset($department->DepartmentHead->email)) {
                        $departmentHeadEmail = $department->DepartmentHead->email;
                        $employeeName = $leave->employee->full_name;
                        $totalDays = $leave->total_days;
                        $startDate = $leave->start_date;
                        $endDate = $leave->end_date;
                        $leaveReason = $leave->leave_reason;

                        // Do not block leave submission on slow SMTP.
                        dispatch(function () use ($departmentHeadEmail, $employeeName, $totalDays, $startDate, $endDate, $leaveReason) {
                            Notification::route('mail', $departmentHeadEmail)
                                ->notify(new EmployeeLeaveNotification(
                                    $employeeName,
                                    $totalDays,
                                    $startDate,
                                    $endDate,
                                    $leaveReason
                                ));
                        })->afterResponse();
                    }

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
            $data = leave::findOrFail($id);
            $company_name = $data->company->company_name ?? '';
            $employee_name = $data->employee->full_name;
            $department = $data->department->department_name ?? '';
            $leave_type_name = $data->LeaveType->leave_type ?? '';

            $start_date_name = $data->start_date;
            $end_date_name = $data->end_date;


            return response()->json([
                'data' => $data, 'employee_name' => $employee_name, 'company_name' => $company_name, 'department' => $department, 'leave_type_name' => $leave_type_name,
                'start_date_name' => $start_date_name, 'end_date_name' => $end_date_name
            ]);
        }
    }

    public function edit($id)
    {
        if (request()->ajax()) {
            $data = leave::findOrFail($id);

            $leaveStartDate = date('Y-m-d', strtotime($data->start_date));

            $departments = department::select('id', 'department_name')
                ->where('company_id', $data->company_id)->get();

            $employees = Employee::select('id', 'first_name', 'last_name')->where('department_id', $data->department_id)->where('is_active', 1)->where('exit_date', NULL)->get();

            return response()->json(['data' => $data, 'employees' => $employees, 'departments' => $departments, 'leaveStartDate' => $leaveStartDate]);
        }
    }

    public function update(Request $request)
    {
        $logged_user = auth()->user();
        $id = $request->hidden_id;
        $leaveForPermission = leave::with('department:id,department_head')->find($id);
        $isDepartmentManager = $leaveForPermission
            ? (int) $leaveForPermission->department?->department_head === (int) auth()->id()
            : false;

        if ($logged_user->can('edit-leave') || $isDepartmentManager) {

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
                            $this->notifyWfhEvent($leave, 'approved');
                        } elseif ($leave->status === 'rejected') {
                            $this->notifyWfhEvent($leave, 'rejected');
                        } else {
                            $this->notifyWfhEvent($leave, 'pending');
                        }
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
        return response()->json(['success' => __('You are not authorized')]);
    }

    public function decision(Request $request, $id)
    {
        $status = strtolower((string) $request->status);
        if (!in_array($status, ['approved', 'rejected'], true)) {
            return response()->json(['error' => __('Invalid status')]);
        }

        $leave = leave::findOrFail($id);

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
        $requestStatus = $request->status;
        if (! in_array($requestStatus, ['approved', 'rejected', 'pending'], true)) {
            $requestStatus = 'pending';
        }

        $currentManagerStatus = $leave->manager_approval_status ?: 'pending';

        $isDepartmentManager = (int) $leave->department?->department_head === (int) auth()->id();

        if ($isDepartmentManager) {
            $data['manager_approval_status'] = $requestStatus;
            $currentManagerStatus = $requestStatus;
        }

        if ($currentManagerStatus === 'rejected') {
            $data['status'] = 'rejected';
        } elseif ($currentManagerStatus === 'approved') {
            $data['status'] = 'approved';
        } else {
            $data['status'] = 'pending';
        }

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

    private function notifyWfhEvent(leave $leave, string $event): void
    {
        $employee = User::find($leave->employee_id);
        $departmentHeadId = department::where('id', $leave->department_id)->value('department_head');
        $departmentHeadUser = $departmentHeadId ? User::find($departmentHeadId) : null;
        // Use the same permission-based audience as leave notifications,
        // so WFH alerts are delivered to the expected approvers.
        $roleIds = DB::table('role_has_permissions')->where('permission_id', 294)->pluck('role_id');
        $roleIds[] = 1; // admin fallback
        $permissionUsers = User::query()->whereIn('role_users_id', $roleIds)->get();

        $link = route('leaves.index');
        if ($event === 'requested') {
            $subject = 'WFH request submitted';
            $message = 'A new WFH request has been submitted.';
        } elseif ($event === 'approved') {
            $subject = 'WFH request approved';
            $message = 'WFH request has been approved.';
        } elseif ($event === 'rejected') {
            $subject = 'WFH request rejected';
            $message = 'WFH request has been rejected.';
        } else {
            $subject = 'WFH request updated';
            $message = 'WFH request status is pending.';
        }

        $requestorName = optional($leave->employee)->full_name ?? 'Employee';
        $eventMessage = $message . ' (' . $requestorName . ')';

        $recipients = collect()
            ->merge($permissionUsers);

        if ($departmentHeadUser) {
            $recipients->push($departmentHeadUser);
        }
        if ($employee) {
            $recipients->push($employee);
        }

        $recipients = $recipients->filter()->unique('id');

        foreach ($recipients as $recipient) {
            $recipientLink = (int) $recipient->id === (int) $leave->employee_id
                ? route('profile') . '#WFH'
                : $link;

            // Always store in-app notification first.
            $recipient->notify(new WfhRequestNotificationToApprover($eventMessage, $recipientLink));

            // Try email separately so DB notification is not blocked by mail issues.
            try {
                $recipient->notify(new WfhEventNotification($subject, $eventMessage, $recipientLink));
            } catch (\Throwable $e) {
                // Fail-safe: keep in-app notification even if mail transport fails.
            }
        }
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
