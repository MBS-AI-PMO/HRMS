<?php

namespace App\Http\Controllers;

use App\Http\traits\LeaveTypeDataManageTrait;
use App\Http\traits\SendsEmployeeCredentialsTrait;
use App\Scopes\AuthCompanyScope;
use App\Support\ClientDisplay;
use App\Support\CompanyScope;
use App\Imports\UsersImport;
use App\Models\Client;
use App\Models\company;
use App\Models\DeductionType;
use App\Models\department;
use App\Models\designation;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeActivityLog;
use App\Models\Team;
use App\Models\GeneralSetting;
use App\Models\LoanType;
use App\Models\office_shift;
use App\Models\QualificationEducationLevel;
use App\Models\QualificationLanguage;
use App\Models\QualificationSkill;
use App\Models\RelationType;
use App\Models\status;
use App\Models\User;
use App\Models\location;
use App\Models\Project;
use App\Support\ManagedEmployeeScope;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Spatie\Permission\Models\Role;
use Throwable;

class EmployeeController extends Controller
{
    use LeaveTypeDataManageTrait, SendsEmployeeCredentialsTrait;

    /**
     * Self-service profile: work/HR fields are view-only (same as mobile app).
     * Only admins with HR modify permission may edit work fields on their own profile.
     */
    protected function employeeSelfProfileWorkReadonly(User $user, int $employeeId): bool
    {
        if ((int) $user->id !== $employeeId) {
            return false;
        }

        return ! ((int) $user->role_users_id === 1 && $user->can('modify-details-employee'));
    }

    protected function employeeListRestrictedToTeamMembers(): bool
    {
        return ManagedEmployeeScope::canAccessScopedEmployeeList(
            (int) auth()->id(),
            (int) auth()->user()->role_users_id
        );
    }

    protected function canAccessEmployeeList(): bool
    {
        if (auth()->user()->can('view-details-employee')) {
            return true;
        }

        return ManagedEmployeeScope::canAccessScopedEmployeeList(
            (int) auth()->id(),
            (int) auth()->user()->role_users_id
        );
    }

    protected function canViewEmployeeRecord(Employee $employee): bool
    {
        if ((int) auth()->user()->role_users_id === 1) {
            return true;
        }

        if ($this->employeeListRestrictedToTeamMembers()) {
            return in_array(
                (int) $employee->id,
                ManagedEmployeeScope::managedEmployeeIds((int) auth()->id()),
                true
            );
        }

        if (ManagedEmployeeScope::canViewScopedEmployeeDetails(
            (int) auth()->id(),
            (int) auth()->user()->role_users_id
        )) {
            return in_array(
                (int) $employee->id,
                ManagedEmployeeScope::managedEmployeeIds((int) auth()->id()),
                true
            );
        }

        return auth()->user()->can('view-details-employee');
    }

    protected function assertCanModifyEmployees(): void
    {
        if (auth()->user()->can('modify-details-employee')) {
            return;
        }

        if ($this->employeeListRestrictedToTeamMembers()) {
            abort(403, __('You are not authorized'));
        }
    }

    protected function employeeListQuery(bool $crossCompany = false)
    {
        if ($crossCompany) {
            return Employee::withoutGlobalScope(AuthCompanyScope::class)
                ->with('user:id,profile_photo,username', 'company:id,company_name', 'client:id,first_name,last_name,company_name', 'department:id,department_name', 'designation:id,designation_name', 'officeShift:id,shift_name');
        }

        return Employee::with('user:id,profile_photo,username', 'company:id,company_name', 'client:id,first_name,last_name,company_name', 'department:id,department_name', 'designation:id,designation_name', 'officeShift:id,shift_name');
    }

    protected function userCanViewEmployeesAtLocation(int $userId, int $locationId): bool
    {
        $user = auth()->user();

        if ($user->can('view-details-employee') || $user->can('view-location')) {
            return true;
        }

        return in_array($locationId, location::locationIdsHeadedByUser($userId), true);
    }

    /**
     * @return array<int>|null  null = no location filter, [] = no access
     */
    protected function resolveLocationFilterIds(Request $request): ?array
    {
        if (! $request->filled('location_id')) {
            return null;
        }

        $locationId = (int) $request->location_id;

        if (! $this->userCanViewEmployeesAtLocation((int) auth()->id(), $locationId)) {
            return [];
        }

        return [$locationId];
    }

    protected function scopedEmployeeListQuery(bool $crossCompany, ?array $locationIds = null)
    {
        $query = $this->employeeListQuery($crossCompany);

        if ($locationIds !== null && $locationIds !== []) {
            $query = $query->whereIn('location_id', $locationIds);
        }

        return $query;
    }

    protected function getEmployees($request, $currentDate, bool $crossCompany = false, ?array $locationIds = null)
    {
        if ($request->filled('client_id')) {
            $crossCompany = true;
        }

        $query = $this->scopedEmployeeListQuery($crossCompany, $locationIds)
            ->where('is_active', 1)
            ->where(function ($q) use ($currentDate) {
                $q->whereNull('exit_date')
                    ->orWhere('exit_date', '>=', $currentDate)
                    ->orWhere('exit_date', '0000-00-00');
            });

        if ($request->filled('client_id')) {
            $query->where('client_id', (int) $request->client_id);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', (int) $request->company_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', (int) $request->department_id);
        }

        if ($request->filled('designation_id')) {
            $query->where('designation_id', (int) $request->designation_id);
        }

        if ($request->filled('office_shift_id')) {
            $query->where('office_shift_id', (int) $request->office_shift_id);
        }

        if ($request->filled('client_id')) {
            $query->orderBy('client_id');
        } else {
            $query->orderBy('company_id');
        }

        return $query->get();
    }

    public function index(Request $request)
    {
        $logged_user = auth()->user();
        if ($this->canAccessEmployeeList()) {
            $isLocationHead = location::userIsLocationHead((int) $logged_user->id);
            $companies = $isLocationHead && ! $logged_user->can('view-details-employee')
                ? CompanyScope::companiesForLocationHead((int) $logged_user->id)
                : CompanyScope::companiesForSelect();
            $clients = $this->clientsForEmployeeSelect();
            $roles = Role::where('id', '!=', 3)->where('is_active', 1)->select('id', 'name')->get();
            $locations = $isLocationHead && ! $logged_user->can('view-details-employee')
                ? location::withoutGlobalScope(\App\Scopes\AuthCompanyLocationScope::class)
                    ->with('companies:id,company_name')
                    ->headedByUser((int) $logged_user->id)
                    ->select('id', 'location_name', 'max_radius')
                    ->get()
                : location::with('companies:id,company_name')->select('id', 'location_name', 'max_radius')->get();
            $currentDate = date('Y-m-d');
            $teamLeaderViewOnly = $this->employeeListRestrictedToTeamMembers();
            $crossCompanyList = $isLocationHead && $teamLeaderViewOnly;
            $filterLocationId = $request->filled('location_id') ? (int) $request->location_id : null;
            $filterLocationName = $filterLocationId
                ? location::withoutGlobalScope(\App\Scopes\AuthCompanyLocationScope::class)
                    ->where('id', $filterLocationId)
                    ->value('location_name')
                : null;

            if (request()->ajax()) {
                $locationFilterIds = $this->resolveLocationFilterIds($request);

                if ($locationFilterIds !== null && $locationFilterIds === []) {
                    return datatables()->of(collect())->make(true);
                }

                $employees = $this->getEmployees($request, $currentDate, $crossCompanyList, $locationFilterIds);

                if ($teamLeaderViewOnly && $locationFilterIds === null) {
                    $allowedIds = ManagedEmployeeScope::managedEmployeeIds((int) auth()->id());
                    $employees = $employees->whereIn('id', $allowedIds)->values();
                }

                return datatables()->of($employees)
                    ->setRowId(function ($row) {
                        return $row->id;
                    })
                    ->addColumn('name', function ($row) {
                        if ($row->user->profile_photo) {

    $url = url('uploads/profile_photos/'.$row->user->profile_photo);

    $profile_photo = '<img src="'.$url.'"
        class="profile-photo md"
        style="height:35px;width:35px;border-radius:50%;object-fit:cover;" />';

} else {

    $initials = strtoupper(substr($row->first_name ?? 'U', 0, 1));

    if (!empty($row->last_name)) {
        $initials .= strtoupper(substr($row->last_name, 0, 1));
    }

    $profile_photo = '
        <div style="
            width:35px;
            height:35px;
            border-radius:50%;
            background:#7C5CC4;
            color:#fff;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:12px;
            font-weight:700;
        ">
            '.$initials.'
        </div>';
}
                        $name = '<span><a href="employees/'.$row->id.'" class="d-block text-bold" style="color:#24ABF2">'.$row->full_name.'</a></span>';
                        $username = '<span>'.__('file.Username').': '.($row->user->username ?? '').'</span>';
                        $staff_id = '<span>'.__('file.Staff Id').': '.($row->staff_id ?? '').'</span>';
                        $gender = '';
                        if ($row->gender != null) {
                            $gender = '<span>'.__('file.Gender').': '.__('file.'.$row->gender ?? '').'</span></br>';
                        }

                        $shift = '<span>'.__('file.Shift').': '.($row->officeShift->shift_name ?? '').'</span>';
                        if (config('variable.currency_format') == 'suffix') {
                            $salary = '<span>'.__('file.Salary').': '.($row->basic_salary ?? '').' '.config('variable.currency').'</span>';
                        } else {
                            $salary = '<span>'.__('file.Salary').': '.config('variable.currency').' '.($row->basic_salary ?? '').'</span>';
                        }

                        if ($row->payslip_type) {
                            $payslip_type = '<span>'.__('file.Payslip Type').': '.__('file.'.$row->payslip_type).'</span>';
                        } else {
                            $payslip_type = ' ';
                        }

                        return "<div class='d-flex'>
                                        <div class='mr-2'>".$profile_photo.'</div>
                                        <div>'
                                            .$name.'</br>'.$username.'</br>'.$staff_id.'</br>'.$gender.$shift.'</br>'.$salary.'</br>'.$payslip_type;

                    })
                    ->addColumn('company', function ($row) {
                        if ($row->client_id && $row->client) {
                            $company = "<span class='text-bold'>".strtoupper(__('Client')).': '.e(ClientDisplay::label($row->client)).'</span>';
                        } else {
                            $company = "<span class='text-bold'>".strtoupper($row->company->company_name ?? '').'</span>';
                        }
                        $department = '<span>'.__('file.Department').' : '.($row->department->department_name ?? '').'</span>';
                        $designation = '<span>'.__('file.Designation').' : '.($row->designation->designation_name ?? '').'</span>';

                        return $company.'</br>'.$department.'</br>'.$designation;
                    })
                    ->addColumn('contacts', function ($row) {
                        $email = "<i class='fa fa-envelope text-muted' title='Email'></i> ".$row->email;
                        $contact_no = "<i class='text-muted fa fa-phone' title='Phone'></i> ".$row->contact_no;
                        $skype_id = "<i class='text-muted fa fa-skype' title='Skype'></i> ".$row->skype_id;
                        $whatsapp_id = "<i class='text-muted fa fa-whatsapp' title='Whats App'></i> ".$row->whatsapp_id;

                        return $email.'</br>'.$contact_no.'</br>'.$skype_id.'</br>'.$whatsapp_id;
                    })
                    ->addColumn('action', function ($data) use ($teamLeaderViewOnly) {
                        $button = '';

                        if ($teamLeaderViewOnly || auth()->user()->can('view-details-employee')) {
                            $button .= '<a href="employees/'.$data->id.'" class="view btn btn-info btn-sm" data-toggle="tooltip" data-placement="top" title="'.__('View Details').'"><i class="dripicons-preview"></i></a>';
                        }

                        if (! $teamLeaderViewOnly && auth()->user()->can('modify-details-employee')) {
                            $button .= '&nbsp;&nbsp;&nbsp;';
                            if ($data->role_users_id != 1 && ! location::userIsLocationHead((int) $data->id)) {
                                $button .= '<button type="button" name="delete" id="'.$data->id.'" class="delete btn btn-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Delete"><i class="dripicons-trash"></i></button>';
                                $button .= '&nbsp;&nbsp;&nbsp;';
                            }

                            $button .= '<a class="download btn-sm" style="background:#FF7588; color:#fff" title="PDF" href="'.route('employees.pdf', $data->id).'"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a>';
                        }

                        return $button;
                    })
                    ->rawColumns(['name', 'company', 'contacts', 'action'])
                    ->make(true);
            }
            return view('employee.index', compact(
                'companies',
                'clients',
                'roles',
                'locations',
                'teamLeaderViewOnly',
                'filterLocationId',
                'filterLocationName'
            ));
        }

        if (request()->ajax()) {
            return response()->json(['success' => __('You are not authorized')]);
        }

        return abort(403, __('You are not authorized'));
    }

    public function store(Request $request)
    {
        $logged_user = auth()->user();

        if (request()->ajax() && $this->employeeListRestrictedToTeamMembers()) {
            return response()->json(['errors' => [__('You are not authorized')]]);
        }

        $this->assertCanModifyEmployees();

        if ($logged_user->can('store-details-employee')) {
            if (request()->ajax()) {
                $rules = [
                    'first_name' => 'required',
                    'last_name' => 'required',
                    'email' => 'nullable|email|unique:users',
                    'contact_no' => 'required|numeric|unique:users',
                    'date_of_birth' => 'required',
                    'employee_owner_type' => 'required|in:company,client',
                    'company_id' => 'required_if:employee_owner_type,company|nullable|exists:companies,id',
                    'client_id' => 'required_if:employee_owner_type,client|nullable|exists:clients,id',
                    'project_id' => 'nullable|array',
                    'project_id.*' => 'integer|exists:projects,id',
                    'department_id' => 'required',
                    'designation_id' => 'required',
                    'office_shift_id' => 'required',
                    'username' => 'required|unique:users',
                    'role_users_id' => 'required',
                    'password' => 'required|min:4|confirmed',
                    'attendance_type' => 'required',
                    'joining_date' => 'required',
                    'profile_photo' => 'nullable|image|max:10240|mimes:jpeg,png,jpg,gif',
                    'address' => 'nullable|string|max:1000',
                    'location_id' => 'nullable|exists:locations,id|required_if:attendance_type,location_based',
                ];

                $rules = array_merge($rules, $this->cnicRulesForEmployee());

                $validator = Validator::make(
                    $request->only(
                        'first_name', 'last_name', 'email','remove_profile_photo', 'contact_no', 'cnic', 'address', 'date_of_birth', 'gender',
                        'username', 'role_users_id', 'password', 'password_confirmation', 'employee_owner_type',
                        'company_id', 'client_id', 'project_id', 'department_id',
                        'designation_id', 'office_shift_id', 'attendance_type', 'joining_date', 'location_id'
                    ),
                    $rules
                );

                if ($validator->fails()) {
                    return response()->json(['errors' => $validator->errors()->all()]);
                }

                $data = [];
                $data['first_name'] = $request->first_name;
                $data['last_name'] = $request->last_name;
                $data['date_of_birth'] = $request->date_of_birth;
                $data['gender'] = $request->gender;
                $data['department_id'] = $request->department_id;
                try {
                    $this->assignEmployeeOwnerFromRequest($data, $request);
                } catch (Throwable $e) {
                    return response()->json(['errors' => [$e->getMessage()]]);
                }
                $data['designation_id'] = $request->designation_id;
                $data['office_shift_id'] = $request->office_shift_id;

                $data['email'] = strtolower(trim($request->email));
                $data['role_users_id'] = $request->role_users_id;
                $data['contact_no'] = $request->contact_no;
                $this->assignCnicFromRequest($data, $request);
                $data['address'] = $request->address ?: null;
                $data['attendance_type'] = $request->attendance_type; //new
                $data['joining_date'] = $request->joining_date; //new
                $data['location_id'] = $request->location_id ?: null;
                $data['is_active'] = 1;
                $data = $this->filterEmployeeAttributesForSchema($data);

                $user = [];
                $user['first_name'] = $request->first_name;
                $user['last_name'] = $request->last_name;
                $user['username'] = strtolower(trim($request->username));
                $user['email'] = strtolower(trim($request->email));
                $user['password'] = bcrypt($request->password);
                $user['role_users_id'] = $request->role_users_id;
                $user['contact_no'] = $request->contact_no;
                $user['is_active'] = 1;

                $photo = $request->profile_photo;
                $file_name = null;

                if (isset($photo)) {
                    $new_user = $request->username;
                    if ($photo->isValid()) {
                        $file_name = preg_replace('/\s+/', '', $new_user).'_'.time().'.'.$photo->getClientOriginalExtension();
                        $photo->storeAs('profile_photos', $file_name);
                        $user['profile_photo'] = $file_name;
                    }
                }

                User::prepareRegistrationStorage($user);

                DB::beginTransaction();
                try {
                    $data['staff_id'] = Employee::generateStaffId();

                    $created_user = User::createAccount($user);
                    $created_user->syncRoles($request->role_users_id); //new

                    $employee = Employee::createForUser($created_user, $data);
                    $this->allLeaveTypeDataNewlyStore($employee);
                    $this->syncEmployeeProjectsFromRequest($employee, $request);

                    if (DB::transactionLevel() > 0) {
                        DB::commit();
                    }
                } catch (Exception $e) {
                    if (DB::transactionLevel() > 0) {
                        DB::rollBack();
                    }

                    return response()->json(['error' => $e->getMessage()]);
                } catch (Throwable $e) {
                    if (DB::transactionLevel() > 0) {
                        DB::rollBack();
                    }

                    return response()->json(['error' => $e->getMessage()]);
                }

                $plainPassword = (string) $request->password;

                try {
                    $emailSent = $this->sendEmployeeCredentialsEmail(
                        $created_user,
                        $plainPassword,
                        $employee->staff_id,
                        $this->credentialsDetailsFromEmployee($employee)
                    );
                } catch (Throwable $e) {
                    return response()->json([
                        'success' => __('Data Added successfully.'),
                        'staff_id' => $employee->staff_id,
                        'email_sent' => false,
                        'warning' => __('Employee created but login email could not be prepared: :message', [
                            'message' => $e->getMessage(),
                        ]),
                    ]);
                }

                $message = __('Data Added successfully.');
                if (! $emailSent && $created_user->email) {
                    $message .= ' '.__('Employee created but login email could not be sent. Please share credentials manually.');
                } elseif ($emailSent) {
                    $message .= ' '.__('Login credentials were sent to the mail server. Ask the employee to check inbox and spam/junk folder.');
                }

                return response()->json([
                    'success' => $message,
                    'staff_id' => $employee->staff_id,
                    'email_sent' => $emailSent,
                ]);
            }
        }

        return response()->json(['success' => __('You are not authorized')]);
    }

    public function show(Employee $employee)
    {
            $employee->loadMissing(['user', 'company', 'client', 'department', 'designation', 'officeShift', 'projects:id,title']);

        if (! $employee->user) {
            return redirect()
                ->route('employees.index')
                ->with('error', __('This employee has no linked user account (ID :id). Please contact administrator.', ['id' => $employee->id]));
        }

        if ($this->canViewEmployeeRecord($employee)) {
            $employeeViewOnly = $this->employeeListRestrictedToTeamMembers();
            $companies = CompanyScope::companiesForSelect();
            $departments = department::select('id', 'department_name')
                ->where('company_id', $employee->company_id)
                ->get();

            $designations = designation::select('id', 'designation_name')
                ->where('department_id', $employee->department_id)
                ->get();

            $office_shifts = $this->officeShiftsForEmployee($employee);

            $statuses = status::select('id', 'status_title')->get();
            // $roles = Role::select('id', 'name')->get();
            $countries = DB::table('countries')->select('id', 'name')->get();
            $document_types = DocumentType::select('id', 'document_type')->get();

            $education_levels = QualificationEducationLevel::select('id', 'name')->get();
            $language_skills = QualificationLanguage::select('id', 'name')->get();
            $general_skills = QualificationSkill::select('id', 'name')->get();
            $relationTypes = RelationType::select('id','type_name')->get();
            $loanTypes = LoanType::select('id','type_name')->get();
            $deductionTypes = DeductionType::select('id','type_name')->get();
            $roles = Role::where('id', '!=', 3)->where('is_active', 1)->select('id', 'name')->get();
            $locations = location::with('companies:id,company_name')->select('id', 'location_name', 'max_radius')->get();
            $clients = $this->clientsForEmployeeSelect();

            return view('employee.dashboard', compact('employee', 'countries', 'companies', 'clients',
                'departments', 'designations', 'statuses', 'office_shifts', 'document_types',
                'education_levels', 'language_skills', 'general_skills', 'roles','relationTypes','loanTypes','deductionTypes', 'locations', 'employeeViewOnly'));
        }

        return abort(403, __('You are not authorized'));
    }

     public function profile()
    {
        $user = Auth::user();
        $employee = Employee::with(['client', 'officeShift', 'projects:id,title'])->find($user->id);

        if (! $employee) {
            $companies = CompanyScope::companiesForSelect();
            $roles = Role::where('id', '!=', 3)->where('is_active', 1)->select('id', 'name')->get();
            $currentDate = date('Y-m-d');

            if ($user->role_users_id == 3) {
                return view('profile.client_profile', compact('user'));
            }

            return view('profile.user_profile', compact('user', 'companies', 'roles', 'currentDate'));
        }

            $companies = CompanyScope::companiesForSelect();
            $departments = department::select('id', 'department_name')
                ->where('company_id', $employee->company_id)
                ->get();

            $designations = designation::select('id', 'designation_name')
                ->where('department_id', $employee->department_id)
                ->get();

            $office_shifts = $this->officeShiftsForEmployee($employee);

            $statuses = status::select('id', 'status_title')->get();
            // $roles = Role::select('id', 'name')->get();
            $countries = DB::table('countries')->select('id', 'name')->get();
            $document_types = DocumentType::select('id', 'document_type')->get();

            $education_levels = QualificationEducationLevel::select('id', 'name')->get();
            $language_skills = QualificationLanguage::select('id', 'name')->get();
            $general_skills = QualificationSkill::select('id', 'name')->get();
            $relationTypes = RelationType::select('id','type_name')->get();
            $loanTypes = LoanType::select('id','type_name')->get();
            $deductionTypes = DeductionType::select('id','type_name')->get();
            $roles = Role::where('id', '!=', 3)->where('is_active', 1)->select('id', 'name')->get();
            $locations = location::with('companies:id,company_name')->select('id', 'location_name', 'max_radius')->get();
            $clients = $this->clientsForEmployeeSelect();

            $workFieldsReadonly = $this->employeeSelfProfileWorkReadonly($user, (int) $employee->id);

            return view('employee.profile', compact('employee', 'countries', 'companies', 'clients',
                'departments', 'designations', 'statuses', 'office_shifts', 'document_types',
                'education_levels', 'language_skills', 'general_skills', 'roles','relationTypes','loanTypes','deductionTypes', 'locations', 'workFieldsReadonly'));

    }

    public function profileActivityLogs(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::find($user->id);

        if (! $employee) {
            return datatables()->of(collect())->make(true);
        }

        $logs = EmployeeActivityLog::with([
            'performer:id,username',
        ])->where('employee_id', $employee->id)->orderByDesc('id');

        if ($request->activity_date) {
            $logs->whereDate('created_at', Carbon::parse($request->activity_date)->format('Y-m-d'));
        }

        return datatables()->of($logs)
            ->setRowId(function ($row) {
                return $row->id;
            })
            ->addColumn('action', function ($row) {
                return $row->action ?? '---';
            })
            ->addColumn('description', function ($row) {
                return $row->description ?? '---';
            })
            ->addColumn('performed_by', function ($row) {
                return optional($row->performer)->username ?? __('System');
            })
            ->addColumn('ip_address', function ($row) {
                return $row->ip_address ?? '---';
            })
            ->addColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format(env('Date_Format') . ' H:i');
            })
            ->rawColumns([])
            ->make(true);
    }

    public function destroy($id)
    {
        $this->assertCanModifyEmployees();

        if (! env('USER_VERIFIED')) {
            return response()->json(['error' => 'This feature is disabled for demo!']);
        }
        $logged_user = auth()->user();

        if ($logged_user->can('modify-details-employee')) {
            DB::beginTransaction();
            try {
                // Check if the employee is referenced in the employee_interview table
                $relatedInterviews = DB::table('employee_interview')->where('employee_id', $id)->exists();

                if ($relatedInterviews) {
                    return response()->json([
                        'error' => 'This employee is linked to interviews. Please delete the related interviews first.'
                    ]);
                }

                $locationHeadBlock = location::deletionBlockReasonForLocationHead((int) $id);
                if ($locationHeadBlock !== null) {
                    return response()->json(['error' => $locationHeadBlock]);
                }

                Employee::whereId($id)->delete();
                $this->unlink($id);
                User::whereId($id)->delete();

                DB::commit();
            } catch (Throwable $e) {
                DB::rollback();
                return response()->json(['error' => $e->getMessage()]);
            }

            return response()->json(['success' => __('Data is successfully deleted')]);
        }

        return response()->json(['success' => __('You are not authorized')]);
    }

    public function unlink($employee)
    {

        $user = User::findOrFail($employee);
        $file_path = $user->profile_photo;

        if ($file_path) {
            $file_path = public_path('uploads/profile_photos/'.$file_path);
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }

    private function normalizeCnic(?string $cnic): ?string
    {
        if ($cnic === null || $cnic === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $cnic);
        if (strlen($digits) !== 13) {
            return trim($cnic);
        }

        return substr($digits, 0, 5).'-'.substr($digits, 5, 7).'-'.substr($digits, 12, 1);
    }

    private function cnicRulesForEmployee(?int $employeeId = null): array
    {
        if (! Schema::hasColumn('employees', 'cnic')) {
            return [];
        }

        $uniqueRule = $employeeId
            ? 'unique:employees,cnic,'.$employeeId
            : 'unique:employees,cnic';

        return [
            'cnic' => ['required', 'regex:/^[0-9]{5}-?[0-9]{7}-?[0-9]{1}$/', $uniqueRule],
        ];
    }

    private function assignCnicFromRequest(array &$data, Request $request): void
    {
        if (Schema::hasColumn('employees', 'cnic')) {
            $data['cnic'] = $this->normalizeCnic($request->cnic);
        }
    }

    public function delete_by_selection(Request $request)
    {
        $this->assertCanModifyEmployees();

        if (! env('USER_VERIFIED')) {
            return response()->json(['error' => 'This feature is disabled for demo!']);
        }
        $logged_user = auth()->user();

        if ($logged_user->can('modify-details-employee')) {
            $employee_id = $request['employeeIdArray'];

            foreach ((array) $employee_id as $selectedId) {
                $locationHeadBlock = location::deletionBlockReasonForLocationHead((int) $selectedId);
                if ($locationHeadBlock !== null) {
                    return response()->json(['error' => $locationHeadBlock]);
                }
            }

            $user = User::whereIntegerInRaw('id', $employee_id)->where('role_users_id', '!=', 1);

            if ($user->delete()) {
                return response()->json(['success' => __('Data is successfully deleted')]);
            }
        }

        return response()->json(['success' => __('You are not authorized')]);
    }

   public function infoUpdate(Request $request, $employee)
{
    $logged_user = auth()->user();
    $employeeId = (int) $employee;
    $employeeModel = Employee::findOrFail($employeeId);

    if ($employeeId !== (int) $logged_user->id) {
        $this->assertCanModifyEmployees();

        if (! $logged_user->can('modify-details-employee')) {
            return response()->json(['errors' => [__('You are not authorized')]], 403);
        }

        if (! $this->canViewEmployeeRecord($employeeModel)) {
            return response()->json(['errors' => [__('You are not authorized')]], 403);
        }
    } elseif (! $logged_user->can('modify-details-employee')) {
        return $this->profileUpdate($request, $employee);
    }

    if ($logged_user->can('modify-details-employee')) {
        if (request()->ajax()) {
            $validator = Validator::make(
                $request->only(
                    'first_name', 'last_name', 'staff_id', 'email', 'contact_no', 'cnic', 'date_of_birth', 'gender',
                    'username', 'password', 'password_confirmation', 'role_users_id', 'employee_owner_type',
                    'company_id', 'client_id', 'project_id', 'department_id',
                    'designation_id', 'office_shift_id', 'location_id', 'status_id', 'marital_status', 'joining_date',
                    'permission_role_id', 'address', 'city', 'state', 'country', 'zip_code', 'attendance_type',
                    'total_leave'
                ),
                array_merge([
                    'first_name'      => 'required',
                    'last_name'       => 'required',
                    'username'        => 'required|unique:users,username,' . $employee,
                    'staff_id'        => 'required|string|max:191|unique:employees,staff_id,' . $employee,
                    'email'           => 'nullable|email|unique:users,email,' . $employee,
                    'contact_no'      => 'required|numeric|unique:users,contact_no,' . $employee,
                    'password'        => 'nullable|min:4|confirmed',
                    'date_of_birth'   => 'required',
                    'department_id'   => 'required',
                    'designation_id'  => 'required',
                    'office_shift_id' => 'required',
                    'role_users_id'   => 'required',
                    'attendance_type' => 'required',
                    'total_leave'     => 'numeric|min:0',
                    'joining_date'    => 'required',
                    'exit_date'       => 'nullable',
                    'location_id'     => 'nullable|exists:locations,id|required_if:attendance_type,location_based',
                    'project_id'      => 'nullable|array',
                    'project_id.*'    => 'integer|exists:projects,id',
                ], $this->employeeOwnerValidationRules(), $this->cnicRulesForEmployee((int) $employee))
            );

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()->all()]);
            }

            $data = [];
            $user = [];
            $file_name = null;

            $photo = $request->profile_photo;

            if (isset($photo) && $photo->isValid()) {
                $new_user = $request->username; // employee_username ki jagah username use karo
                $file_name = preg_replace('/\s+/', '', $new_user) . '_' . time() . '.' . $photo->getClientOriginalExtension();

                $photo->storeAs('profile_photos', $file_name);

                $this->unlink($employee);

                $user['profile_photo'] = $file_name;
            }
            if ($request->remove_profile_photo == 1) {

    $this->unlink($employee);

    $user['profile_photo'] = null;
}

            $data['first_name'] = $request->first_name;
            $data['last_name'] = $request->last_name;
            $data['staff_id'] = $request->staff_id;
            $data['date_of_birth'] = $request->date_of_birth;
            $data['email'] = strtolower(trim($request->email));
            $data['contact_no'] = $request->contact_no;
            $this->assignCnicFromRequest($data, $request);
            $data['gender'] = $request->gender;
            $data['department_id'] = $request->department_id;
            try {
                $this->assignEmployeeOwnerFromRequest($data, $request);
            } catch (Throwable $e) {
                return response()->json(['errors' => [$e->getMessage()]]);
            }
            $data = $this->filterEmployeeAttributesForSchema($data);
            $data['designation_id'] = $request->designation_id;
            $data['office_shift_id'] = $request->office_shift_id;
            $data['location_id'] = $request->location_id ?: null;
            $data['status_id'] = $request->status_id;
            $data['marital_status'] = $request->marital_status;

            if ($request->joining_date) {
                $data['joining_date'] = $request->joining_date;
            }

            $data['exit_date'] = $request->exit_date ? date('Y-m-d', strtotime($request->exit_date)) : null;
            $data['address'] = $request->address;
            $data['city'] = $request->city;
            $data['state'] = $request->state;
            $data['country'] = $request->country;
            $data['zip_code'] = $request->zip_code;
            $data['attendance_type'] = $request->attendance_type;
            $data['is_active'] = 1;
            $data['role_users_id'] = $request->role_users_id;

            $user['first_name'] = $request->first_name;
            $user['last_name'] = $request->last_name;
            $user['username'] = strtolower(trim($request->username));
            $user['email'] = strtolower(trim($request->email));
            $user['role_users_id'] = $request->role_users_id;
            $user['contact_no'] = $request->contact_no;
            $user['is_active'] = 1;

            if ($request->filled('password')) {
                $user['password'] = bcrypt($request->password);
            }

            DB::beginTransaction();
            try {
                User::whereId($employee)->update($user);
                $employeeModel = Employee::find($employee);
                $employeeModel->update($data);

                $usertest = User::find($employee);
                $usertest->syncRoles($request->role_users_id);
                $this->syncEmployeeProjectsFromRequest($employeeModel->fresh(), $request);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['error' => $e->getMessage()]);
            } catch (\Throwable $e) {
                DB::rollback();
                return response()->json(['error' => $e->getMessage()]);
            }

            $updatedEmployee = Employee::find($employee);

return response()->json([
    'success' => __('Data Updated successfully.'),
    'employee' => $updatedEmployee,
    'profile_picture' => $file_name
]);
        }
    }

    return response()->json(['success' => __('You are not authorized')]);
}


   public function profileUpdate(Request $request, $employee)
{
    $logged_user = auth()->user();

    if (! $logged_user) {
        abort(401, __('Unauthenticated.'));
    }

    $employeeId = (int) $employee;
    $isSelf = (int) $logged_user->id === $employeeId;

    if (! $isSelf) {
        if (! $logged_user->can('modify-details-employee')) {
            return response()->json(['errors' => [__('You are not authorized')]], 403);
        }

        $employeeModel = Employee::find($employeeId);

        if (! $employeeModel || ! $this->canViewEmployeeRecord($employeeModel)) {
            return response()->json(['errors' => [__('You are not authorized')]], 403);
        }
    }

    $workReadonly = $this->employeeSelfProfileWorkReadonly($logged_user, $employeeId);

        if (request()->ajax()) {
            if ($workReadonly) {
                $validator = Validator::make(
                    $request->only(
                        'first_name', 'last_name', 'email', 'contact_no', 'cnic', 'date_of_birth', 'gender',
                        'username', 'marital_status', 'address', 'city', 'state', 'country', 'zip_code'
                    ),
                    array_merge([
                        'first_name'    => 'required',
                        'last_name'     => 'required',
                        'username'      => 'required|unique:users,username,' . $employee,
                        'email'         => 'nullable|email|unique:users,email,' . $employee,
                        'contact_no'    => 'required|numeric|unique:users,contact_no,' . $employee,
                        'date_of_birth' => 'required',
                    ], $this->cnicRulesForEmployee((int) $employee))
                );
            } else {
                $validator = Validator::make(
                    $request->only(
                        'first_name', 'last_name', 'staff_id', 'email', 'contact_no', 'cnic', 'date_of_birth', 'gender',
                        'username', 'role_users_id', 'employee_owner_type', 'company_id', 'client_id', 'project_id', 'department_id',
                        'designation_id', 'office_shift_id',
                        'location_id', 'status_id', 'marital_status', 'joining_date', 'permission_role_id', 'address',
                        'city', 'state', 'country', 'zip_code', 'attendance_type', 'total_leave'
                    ),
                    array_merge([
                        'first_name'      => 'required',
                        'last_name'       => 'required',
                        'username'        => 'required|unique:users,username,' . $employee,
                        'staff_id'        => 'required|string|max:191|unique:employees,staff_id,' . $employee,
                        'email'           => 'nullable|email|unique:users,email,' . $employee,
                        'contact_no'      => 'required|numeric|unique:users,contact_no,' . $employee,
                        'date_of_birth'   => 'required',
                        'department_id'   => 'required',
                        'designation_id'  => 'required',
                        'office_shift_id' => 'required',
                        'role_users_id'   => 'required',
                        'attendance_type' => 'required',
                        'total_leave'     => 'numeric|min:0',
                        'joining_date'    => 'required',
                        'exit_date'       => 'nullable',
                        'location_id'     => 'nullable|exists:locations,id|required_if:attendance_type,location_based',
                        'project_id'      => 'nullable|array',
                        'project_id.*'    => 'integer|exists:projects,id',
                    ], $this->employeeOwnerValidationRules(), $this->cnicRulesForEmployee((int) $employee))
                );
            }

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()->all()]);
            }

            $data = [];
            $user = [];
            $file_name = null;

            $photo = $request->profile_photo;

            if (isset($photo) && $photo->isValid()) {
                $new_user = $request->username;
                $file_name = preg_replace('/\s+/', '', $new_user) . '_' . time() . '.' . $photo->getClientOriginalExtension();

                $photo->storeAs('profile_photos', $file_name);

                $this->unlink($employee);

                $user['profile_photo'] = $file_name;
            }
if ($request->remove_profile_photo == 1) {

    $this->unlink($employee);

    $user['profile_photo'] = null;
}
            $data['first_name'] = $request->first_name;
            $data['last_name'] = $request->last_name;
            $data['date_of_birth'] = $request->date_of_birth;
            $data['email'] = strtolower(trim($request->email));
            $data['contact_no'] = $request->contact_no;
            $this->assignCnicFromRequest($data, $request);
            $data['gender'] = $request->gender;
            $data['marital_status'] = $request->marital_status;
            $data['address'] = $request->address;
            $data['city'] = $request->city;
            $data['state'] = $request->state;
            $data['country'] = $request->country;
            $data['zip_code'] = $request->zip_code;
            $data['is_active'] = 1;

            $user['first_name'] = $request->first_name;
            $user['last_name'] = $request->last_name;
            $user['username'] = strtolower(trim($request->username));
            $user['email'] = strtolower(trim($request->email));
            $user['contact_no'] = $request->contact_no;
            $user['is_active'] = 1;

            if (! $workReadonly) {
                $data['staff_id'] = $request->staff_id;
                $data['department_id'] = $request->department_id;
                try {
                    $this->assignEmployeeOwnerFromRequest($data, $request);
                } catch (Throwable $e) {
                    return response()->json(['errors' => [$e->getMessage()]]);
                }
                $data = $this->filterEmployeeAttributesForSchema($data);
                $data['designation_id'] = $request->designation_id;
                $data['office_shift_id'] = $request->office_shift_id;
                $data['location_id'] = $request->location_id ?: null;
                $data['status_id'] = $request->status_id;

                if ($request->joining_date) {
                    $data['joining_date'] = $request->joining_date;
                }

                $data['exit_date'] = $request->exit_date ? date('Y-m-d', strtotime($request->exit_date)) : null;
                $data['attendance_type'] = $request->attendance_type;
                $user['role_users_id'] = $request->role_users_id;
            }

            DB::beginTransaction();
            try {
                User::whereId($employee)->update($user);
                $employeeModel = Employee::find($employee);
                $employeeModel->update($data);

                if (! $workReadonly) {
                    $usertest = User::find($employee);
                    $usertest->syncRoles($request->role_users_id);
                    $this->syncEmployeeProjectsFromRequest($employeeModel->fresh(), $request);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['error' => $e->getMessage()]);
            } catch (\Throwable $e) {
                DB::rollback();
                return response()->json(['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => __('Data Updated successfully.'),
                'profile_picture' => $file_name
            ]);
        }

    }

    private function syncEmployeeProjectsFromRequest(Employee $employee, Request $request): void
    {
        if ($request->input('employee_owner_type') !== 'client' && ! $employee->client_id) {
            return;
        }

        $clientId = (int) ($request->input('client_id') ?: $employee->client_id);

        if (! $clientId) {
            return;
        }

        $projectIds = collect($request->input('project_id', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($projectIds->isEmpty()) {
            // Keep existing assignments if no project field was submitted (e.g. company mode / readonly).
            if (! $request->has('project_id')) {
                return;
            }

            $employee->projects()->detach();

            return;
        }

        $validIds = Project::query()
            ->where('client_id', $clientId)
            ->whereIn('id', $projectIds)
            ->pluck('id')
            ->all();

        $employee->projects()->sync($validIds);
    }

    public function socialProfileShow(Employee $employee)
    {
        return view('employee.social_profile.index', compact('employee'));
    }

    public function storeSocialInfo(Request $request, $employee)
    {
        $logged_user = auth()->user();

        if ($logged_user->can('modify-details-employee') || $logged_user->id == $employee) {
            $data = [];
            $data['fb_id'] = $request->fb_id;
            $data['twitter_id'] = $request->twitter_id;
            $data['linkedIn_id'] = $request->linkedIn_id;
            $data['whatsapp_id'] = $request->whatsapp_id;
            $data['skype_id'] = $request->skype_id;

            Employee::whereId($employee)->update($data);

            return response()->json(['success' => __('Data is successfully updated')]);

        }

        return response()->json(['success' => __('You are not authorized')]);

    }

    public function indexProfilePicture(Employee $employee)
    {
        $logged_user = auth()->user();

        if ($logged_user->can('modify-details-employee')) {
            return view('employee.profile_picture.index', compact('employee'));
        }

        return response()->json(['success' => __('You are not authorized')]);
    }

    public function storeProfilePicture(Request $request, $employee)
    {
        $logged_user = auth()->user();

        if ($logged_user->can('modify-details-employee') || $logged_user->id == $employee) {

            $data = [];
            $photo = $request->profile_photo;
            $file_name = null;

            if (isset($photo)) {
                $new_user = $request->employee_username;
                if ($photo->isValid()) {
                    $file_name = preg_replace('/\s+/', '', $new_user).'_'.time().'.'.$photo->getClientOriginalExtension();
                    $photo->storeAs('profile_photos', $file_name);
                    $data['profile_photo'] = $file_name;
                }
            }

            $this->unlink($employee);

            User::whereId($employee)->update($data);

            return response()->json(['success' => 'Data is successfully updated', 'profile_picture' => $file_name]);

        }

        return response()->json(['success' => __('You are not authorized')]);
    }
public function removeProfilePhoto($employee)
{
    $user = User::find($employee);

    if ($user && $user->profile_photo) {

        $user->profile_photo = null;
        $user->save();

    }

    return response()->json([
        'success' => true
    ]);
}
    public function setSalary(Employee $employee)
    {
        $logged_user = auth()->user();
        if ($logged_user->can('modify-details-employee')) {
            return view('employee.salary.index', compact('employee'));
        }

        return response()->json(['success' => __('You are not authorized')]);
    }

    public function storeSalary(Request $request, $employee)
    {
        $logged_user = auth()->user();

        if ($logged_user->can('modify-details-employee')) {

            $validator = Validator::make($request->only('payslip_type', 'basic_salary'
            ),
                [
                    'basic_salary' => 'required|numeric',
                    'payslip_type' => 'required',
                ]
            );

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()->all()]);
            }

            DB::beginTransaction();
            try {
                Employee::updateOrCreate(['id' => $employee], [
                    'payslip_type' => $request->payslip_type,
                    'basic_salary' => $request->basic_salary]);
                DB::commit();
            } catch (Exception $e) {
                DB::rollback();

                return response()->json(['error' => $e->getMessage()]);
            } catch (Throwable $e) {
                DB::rollback();

                return response()->json(['error' => $e->getMessage()]);
            }

            return response()->json(['success' => __('Data Added successfully.')]);
        }

        return response()->json(['error' => __('You are not authorized')]);
    }

    public function employeesPensionUpdate(Request $request, $employee)
    {
        //return response()->json('ok');
        $logged_user = auth()->user();

        if ($logged_user->can('modify-details-employee')) {

            $validator = Validator::make($request->only('pension_type', 'pension_amount'), [
                'pension_type' => 'required',
                'pension_amount' => 'required|numeric',
            ]
            );

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()->all()]);
            }

            DB::beginTransaction();
            try {
                Employee::updateOrCreate(['id' => $employee], [
                    'pension_type' => $request->pension_type,
                    'pension_amount' => $request->pension_amount]);
                DB::commit();
            } catch (Exception $e) {
                DB::rollback();

                return response()->json(['error' => $e->getMessage()]);
            } catch (Throwable $e) {
                DB::rollback();

                return response()->json(['error' => $e->getMessage()]);
            }

            return response()->json(['success' => __('Data Added successfully.')]);
        }

        return response()->json(['success' => __('You are not authorized')]);

    }

    public function import()
    {

        if (auth()->user()->can('import-employee')) {
            return view('employee.import');
        }

        return abort(404, __('You are not authorized'));
    }

    public function importPost()
    {

        if (! env('USER_VERIFIED')) {
            $this->setErrorMessage('This feature is disabled for demo!');

            return redirect()->back();
        }
        try {
            Excel::queueImport(new UsersImport(), request()->file('file'));
        } catch (ValidationException $e) {
            $failures = $e->failures();

            return view('employee.importError', compact('failures'));
        }

        $this->setSuccessMessage(__('Imported Successfully'));

        return back();

    }

    public function employeePDF($id)
    {
        $this->assertCanModifyEmployees();

        $employeeModel = Employee::findOrFail($id);

        if (! $this->canViewEmployeeRecord($employeeModel)) {
            return abort(403, __('You are not authorized'));
        }

        $employee = $employeeModel->load('user:id,profile_photo,username', 'company:id,company_name', 'department:id,department_name', 'designation:id,designation_name', 'officeShift:id,shift_name', 'role:id,name')
            ->toArray();

        PDF::setOptions(['dpi' => 10, 'defaultFont' => 'sans-serif', 'tempDir' => storage_path('temp')]);
        $pdf = PDF::loadView('employee.pdf', $employee);
        return $pdf->download('employee.pdf');

        // return $pdf->stream();
    }


   public function updateAttendanceType()
{
    try {
        $today = now()->toDateString();

        $activeWfhEmployeeIds = \App\Models\leave::query()
            ->join('leave_types', 'leave_types.id', '=', 'leaves.leave_type_id')
            ->where('leaves.status', 'approved')
            ->where('leaves.manager_approval_status', 'approved')
            ->whereDate('leaves.start_date', '<=', $today)
            ->whereDate('leaves.end_date', '>=', $today)
            ->where(function ($query) {
                $query->where('leave_types.leave_type', 'like', '%wfh%')
                    ->orWhere('leave_types.leave_type', 'like', '%work from home%');
            })
            ->distinct()
            ->pluck('leaves.employee_id');

        $toGeneral = \App\Models\Employee::query()
            ->whereIn('id', $activeWfhEmployeeIds)
            ->where('attendance_type', '!=', 'general')
            ->update(['attendance_type' => 'general']);

        $toLocationBased = \App\Models\Employee::query()
            ->whereNotIn('id', $activeWfhEmployeeIds)
            ->where('attendance_type', 'general')
            ->update(['attendance_type' => 'location_based']);

        return response()->json([
            'success' => true,
            'updated_count' => $toGeneral + $toLocationBased
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
    }
}

    private function employeeOwnerValidationRules(): array
    {
        return [
            'employee_owner_type' => 'required|in:company,client',
            'company_id' => 'required_if:employee_owner_type,company|nullable|exists:companies,id',
            'client_id' => 'required_if:employee_owner_type,client|nullable|exists:clients,id',
        ];
    }

    private function assignEmployeeOwnerFromRequest(array &$data, Request $request): void
    {
        if ($request->input('employee_owner_type') === 'client') {
            if (! Schema::hasColumn('employees', 'client_id')) {
                throw new Exception(__('Client employees are not enabled yet. Please run database migrations first.'));
            }

            $data['client_id'] = (int) $request->client_id;
            // Client-owned employees must not depend on companies.id (company delete must not remove them).
            $data['company_id'] = null;
        } else {
            if (Schema::hasColumn('employees', 'client_id')) {
                $data['client_id'] = null;
            } else {
                unset($data['client_id']);
            }

            $data['company_id'] = CompanyScope::resolveCompanyIdForInput($request->company_id);
        }
    }

    private function filterEmployeeAttributesForSchema(array $data): array
    {
        if (! Schema::hasColumn('employees', 'client_id')) {
            unset($data['client_id']);
        }

        return $data;
    }

    private function officeShiftsForEmployee(Employee $employee)
    {
        $query = office_shift::query()->select('id', 'shift_name');

        if ($employee->client_id) {
            $query->where('client_id', $employee->client_id);
        } elseif ($employee->company_id) {
            $query->where('company_id', $employee->company_id)->whereNull('client_id');
        } else {
            return collect();
        }

        $shifts = $query->orderBy('shift_name')->get();

        if ($employee->office_shift_id && ! $shifts->contains('id', $employee->office_shift_id)) {
            $current = office_shift::query()
                ->select('id', 'shift_name')
                ->find($employee->office_shift_id);

            if ($current) {
                $shifts->push($current);
            }
        }

        return $shifts;
    }

    private function clientsForEmployeeSelect()
    {
        return Client::query()
            ->select('id', 'company_name', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('company_name')
            ->get()
            ->map(function ($client) {
                $client->resolved_company_id = CompanyScope::resolveCompanyIdForClient((int) $client->id);

                return $client;
            });
    }

    private function resolveCompanyIdFromClient(int $clientId): int
    {
        $companyId = CompanyScope::resolveCompanyIdForClient($clientId);

        if (! $companyId) {
            throw new Exception(__('No company found for the selected client.'));
        }

        return (int) $companyId;
    }

}
