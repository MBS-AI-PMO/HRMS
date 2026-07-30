<?php

namespace App\Http\Controllers;

use App\Http\traits\LeaveTypeDataManageTrait;
use App\Http\traits\SendsEmployeeCredentialsTrait;
use App\Models\Client;
use App\Models\ClientRegistrationSetting;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeActivityLog;
use App\Models\OfficeShift;
use App\Models\Project;
use App\Models\User;
use App\Support\CompanyScope;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PublicClientRegistrationController extends Controller
{
    use LeaveTypeDataManageTrait, SendsEmployeeCredentialsTrait;

    public function create(?string $registrationSlug = null)
    {
        $general_setting = DB::table('general_settings')->latest()->first();
        $registrationSetting = null;
        $selectedClient = null;
        $assignedProjects = collect();

        if ($registrationSlug !== null) {
            $registrationSetting = $this->resolveSettingFromKey($registrationSlug);

            if (! $registrationSetting || ! $registrationSetting->is_enabled) {
                return view('client_registration.unavailable', compact('general_setting', 'selectedClient', 'registrationSetting'));
            }

            $registrationSetting->ensureRegistrationSlug();
            $selectedClient = Client::find($registrationSetting->client_id);
            $assignedProjects = $registrationSetting->projects();
        }

        $formFields = $registrationSetting
            ? $registrationSetting->resolvedFormFields()
            : ClientRegistrationSetting::defaultFormFields();

        $companyId = $selectedClient
            ? CompanyScope::resolveCompanyIdForClient((int) $selectedClient->id)
            : null;

        $departments = $companyId
            ? Department::where('company_id', $companyId)->orderBy('department_name')->get(['id', 'department_name'])
            : collect();

        $officeShifts = $companyId
            ? OfficeShift::where('company_id', $companyId)->orderBy('shift_name')->get(['id', 'shift_name'])
            : collect();

        $orgSettings = [
            'show_department' => (bool) ($registrationSetting?->allow_department_selection ?? true),
            'show_designation' => (bool) ($registrationSetting?->allow_designation_selection ?? true),
            'show_shift' => (bool) ($registrationSetting?->allow_shift_selection ?? false),
        ];

        return view('client_registration.form', compact(
            'general_setting',
            'selectedClient',
            'registrationSetting',
            'formFields',
            'departments',
            'officeShifts',
            'orgSettings',
            'assignedProjects',
            'companyId'
        ));
    }

    public function config(string $registrationKey)
    {
        $setting = $this->resolveSettingFromKey($registrationKey);

        if (! $setting || ! $setting->is_enabled) {
            return response()->json(['error' => __('Registration is not enabled for this link.')], 404);
        }

        $client = Client::find($setting->client_id);
        $setting->ensureRegistrationSlug();

        return response()->json([
            'client_name' => $client ? trim(($client->company_name ?: '').' '.($client->first_name ?? '').' '.($client->last_name ?? '')) : '',
            'projects' => $setting->projects()->map(fn ($p) => ['id' => $p->id, 'title' => $p->title])->values(),
            'setting' => [
                'id' => $setting->id,
                'client_id' => $setting->client_id,
                'page_title' => $setting->page_title,
                'intro_text' => $setting->intro_text,
                'allow_department_selection' => $setting->allow_department_selection,
                'allow_designation_selection' => $setting->allow_designation_selection,
                'allow_shift_selection' => $setting->allow_shift_selection,
                'default_department_id' => $setting->default_department_id,
                'default_designation_id' => $setting->default_designation_id,
                'default_office_shift_id' => $setting->default_office_shift_id,
            ],
            'form_fields' => $setting->resolvedFormFields(),
        ]);
    }

    public function departments(Request $request)
    {
        $setting = $this->settingFromRequest($request);
        if (! $setting) {
            return response('', 403);
        }

        $companyId = CompanyScope::resolveCompanyIdForClient((int) $setting->client_id);
        if (! $companyId) {
            return response('', 403);
        }

        $rows = Department::where('company_id', $companyId)->select('id', 'department_name')->get();
        $output = '<option value=""></option>';
        foreach ($rows as $row) {
            $output .= '<option value="'.$row->id.'">'.$row->department_name.'</option>';
        }

        return $output;
    }

    public function designations(Request $request)
    {
        $setting = $this->settingFromRequest($request);
        if (! $setting) {
            return response('', 403);
        }

        $departmentId = (int) $request->department_id;
        $department = Department::find($departmentId);
        $companyId = CompanyScope::resolveCompanyIdForClient((int) $setting->client_id);

        if (! $department || ! $companyId || (int) $department->company_id !== $companyId) {
            return response('', 403);
        }

        $rows = Designation::where('department_id', $departmentId)->select('id', 'designation_name')->get();
        $output = '<option value=""></option>';
        foreach ($rows as $row) {
            $output .= '<option value="'.$row->id.'">'.$row->designation_name.'</option>';
        }

        return $output;
    }

    public function shifts(Request $request)
    {
        $setting = $this->settingFromRequest($request);
        if (! $setting) {
            return response('', 403);
        }

        $companyId = CompanyScope::resolveCompanyIdForClient((int) $setting->client_id);
        if (! $companyId) {
            return response('', 403);
        }

        $rows = OfficeShift::where('company_id', $companyId)->select('id', 'shift_name')->get();
        $output = '<option value=""></option>';
        foreach ($rows as $row) {
            $output .= '<option value="'.$row->id.'">'.$row->shift_name.'</option>';
        }

        return $output;
    }

    public function store(Request $request)
    {
        $settingId = (int) $request->registration_setting_id;
        $setting = ClientRegistrationSetting::where('id', $settingId)
            ->where('is_enabled', true)
            ->first();

        if (! $setting) {
            return response()->json(['error' => __('Registration is not enabled for this link.')], 403);
        }

        $clientId = (int) $setting->client_id;
        $projectIds = $setting->resolvedProjectIds();
        $companyId = CompanyScope::resolveCompanyIdForClient($clientId);

        if ($projectIds === []) {
            return response()->json(['error' => __('No projects are configured for this registration link.')], 422);
        }

        $fields = $setting->resolvedFormFields();
        $rules = [
            'registration_setting_id' => 'required|exists:client_registration_settings,id',
        ];
        $messages = [];

        foreach ($fields as $name => $config) {
            if (empty($config['enabled'])) {
                continue;
            }
            $rule = [];
            if (! empty($config['required'])) {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }
            switch ($name) {
                case 'email':
                    $rule[] = 'email';
                    $rule[] = 'unique:users,email';
                    break;
                case 'contact_no':
                    $rule[] = 'numeric';
                    $rule[] = 'unique:users,contact_no';
                    break;
                case 'cnic':
                    $rule[] = 'regex:/^[0-9]{5}-?[0-9]{7}-?[0-9]{1}$/';
                    $rule[] = 'unique:employees,cnic';
                    break;
                case 'username':
                    $rule[] = 'unique:users,username';
                    break;
                case 'profile_photo':
                    $rule = ! empty($config['required']) ? ['required', 'image', 'max:10240', 'mimes:jpeg,png,jpg,gif'] : ['nullable', 'image', 'max:10240', 'mimes:jpeg,png,jpg,gif'];
                    break;
                case 'date_of_birth':
                case 'joining_date':
                    $rule[] = 'date';
                    break;
                default:
                    $rule[] = 'string';
            }
            $rules[$name] = $rule;
        }

        if ($setting->allow_department_selection || $request->filled('department_id')) {
            $rules['department_id'] = 'required|exists:departments,id';
        }
        if ($setting->allow_designation_selection || $request->filled('designation_id')) {
            $rules['designation_id'] = 'required|exists:designations,id';
        }
        if ($setting->allow_shift_selection) {
            $rules['office_shift_id'] = 'required|exists:office_shifts,id';
        }

        $rules['email'] = ['required', 'email', 'unique:users,email'];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $plainPassword = Employee::generatePassword();

        $departmentId = ($setting->allow_department_selection || $request->filled('department_id'))
            ? (int) $request->department_id
            : (int) $setting->default_department_id;
        $designationId = ($setting->allow_designation_selection || $request->filled('designation_id'))
            ? (int) $request->designation_id
            : (int) $setting->default_designation_id;
        $officeShiftId = $setting->allow_shift_selection
            ? (int) $request->office_shift_id
            : (int) $setting->default_office_shift_id;

        if (! $officeShiftId && $companyId) {
            $officeShiftId = (int) OfficeShift::where('company_id', $companyId)->value('id');
        }

        if (! $departmentId || ! $designationId || ! $officeShiftId) {
            return response()->json(['error' => __('Registration defaults are not configured. Please contact administrator.')], 422);
        }

        $department = Department::where('id', $departmentId)->where('company_id', $companyId)->first();
        $designation = Designation::where('id', $designationId)->where('department_id', $departmentId)->first();
        $shift = OfficeShift::where('id', $officeShiftId)->where('company_id', $companyId)->first();

        if (! $department || ! $designation || ! $shift) {
            return response()->json(['error' => __('Invalid department, designation or shift.')], 422);
        }

        try {
            if (! empty($fields['joining_date']['enabled']) && $request->joining_date) {
                $joining = new DateTime($request->joining_date);
            } else {
                $joining = new DateTime(now()->format('Y-m-d'));
            }
            if (! empty($fields['date_of_birth']['enabled']) && $request->date_of_birth) {
                $dob = new DateTime($request->date_of_birth);
                if ($dob >= $joining) {
                    return response()->json(['error' => __('Date of birth must be before joining date.')], 422);
                }
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $isActive = $setting->auto_approve ? 1 : 0;
        $joiningDate = ! empty($fields['joining_date']['enabled']) && $request->joining_date
            ? $request->joining_date
            : now()->format(config('variable.date_format', 'd-m-Y'));

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth ?? now()->subYears(18)->format(config('variable.date_format', 'd-m-Y')),
            'gender' => $request->gender,
            'department_id' => $departmentId,
            'company_id' => null,
            'client_id' => $clientId,
            'designation_id' => $designationId,
            'office_shift_id' => $officeShiftId,
            'email' => strtolower(trim((string) $request->email)),
            'contact_no' => $request->contact_no,
            'cnic' => ! empty($fields['cnic']['enabled']) && $request->cnic && Schema::hasColumn('employees', 'cnic')
                ? $this->normalizeCnic($request->cnic)
                : null,
            'attendance_type' => $setting->default_attendance_type,
            'joining_date' => $joiningDate,
            'is_active' => $isActive,
            'role_users_id' => $setting->default_role_users_id ?: 3,
        ];

        $user = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => strtolower(trim((string) $request->username)),
            'email' => strtolower(trim((string) $request->email)),
            'password' => bcrypt($plainPassword),
            'role_users_id' => $setting->default_role_users_id ?: 3,
            'contact_no' => $request->contact_no,
            'is_active' => $isActive,
        ];

        if ($request->hasFile('profile_photo') && ! empty($fields['profile_photo']['enabled'])) {
            $photo = $request->profile_photo;
            if ($photo->isValid()) {
                $file_name = preg_replace('/\s+/', '', $user['username']).'_'.time().'.'.$photo->getClientOriginalExtension();
                $photo->storeAs('profile_photos', $file_name);
                $user['profile_photo'] = $file_name;
            }
        }

        $employee = null;
        $created_user = null;

        User::prepareRegistrationStorage($user);

        DB::beginTransaction();
        try {
            $data['staff_id'] = Employee::generateStaffId();

            $created_user = User::createAccount($user);
            $created_user->syncRoles($setting->default_role_users_id ?: 3);

            $employee = Employee::createForUser($created_user, $data);
            $employee->syncMemberProjects($projectIds);
            $this->allLeaveTypeDataNewlyStore($employee);

            if (Schema::hasTable('employee_activity_logs')) {
                EmployeeActivityLog::create([
                    'employee_id' => $employee->id,
                    'performed_by' => null,
                    'action' => 'employee.client_self_registered',
                    'description' => 'Employee registered via client public form.',
                    'meta' => [
                        'client_id' => $clientId,
                        'project_ids' => $projectIds,
                        'registration_setting_id' => $setting->id,
                        'auto_approve' => $setting->auto_approve,
                    ],
                    'ip_address' => $request->ip(),
                ]);
            }

            if (DB::transactionLevel() > 0) {
                DB::commit();
            }
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Public client registration failed', [
                'client_id' => $clientId,
                'registration_setting_id' => $setting->id,
                'email' => $request->email,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }

        $emailSent = $this->sendEmployeeCredentialsEmail(
            $created_user,
            $plainPassword,
            $employee->staff_id,
            $this->credentialsDetailsFromEmployee($employee)
        );

        $message = $setting->success_message
            ?: __('Registration successful. Login credentials have been sent to your email.');

        if (! $emailSent) {
            $message .= ' '.__('We could not send the login email. Please contact your administrator for your password.');
        }

        return response()->json([
            'success' => $message,
            'staff_id' => $employee->staff_id,
            'email_sent' => $emailSent,
            'project_ids' => $projectIds,
        ]);
    }

    private function resolveSettingFromKey(string $key): ?ClientRegistrationSetting
    {
        if (ctype_digit($key)) {
            return ClientRegistrationSetting::find((int) $key);
        }

        return ClientRegistrationSetting::findByRegistrationSlug($key);
    }

    private function settingFromRequest(Request $request): ?ClientRegistrationSetting
    {
        $settingId = (int) $request->registration_setting_id;
        if (! $settingId) {
            return null;
        }

        $setting = ClientRegistrationSetting::where('id', $settingId)
            ->where('is_enabled', true)
            ->first();

        return $setting;
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
}
