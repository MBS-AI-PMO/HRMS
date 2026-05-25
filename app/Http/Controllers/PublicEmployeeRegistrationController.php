<?php

namespace App\Http\Controllers;

use App\Http\traits\LeaveTypeDataManageTrait;
use App\Http\traits\SendsEmployeeCredentialsTrait;
use App\Models\company;
use App\Models\department;
use App\Models\designation;
use App\Models\Employee;
use App\Models\EmployeeActivityLog;
use App\Models\EmployeeRegistrationSetting;
use App\Models\office_shift;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PublicEmployeeRegistrationController extends Controller
{
    use LeaveTypeDataManageTrait, SendsEmployeeCredentialsTrait;

    public function create(?string $companySlug = null)
    {
        $general_setting = DB::table('general_settings')->latest()->first();
        $selectedCompany = null;
        $registrationEnabledCompanyIds = EmployeeRegistrationSetting::where('is_enabled', true)
            ->pluck('company_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($companySlug !== null) {
            $selectedCompany = $this->resolveCompanyFromKey($companySlug);

            if (! $selectedCompany || ! $this->registrationEnabled((int) $selectedCompany->id)) {
                return view('employee_registration.unavailable', compact('general_setting', 'selectedCompany'));
            }

            $selectedCompany->ensureRegistrationSlug();
            $selectedCompany->refresh();
            $companies = collect([$selectedCompany]);
            $registrationSetting = EmployeeRegistrationSetting::where('company_id', $selectedCompany->id)
                ->where('is_enabled', true)
                ->first();
            $formFields = $registrationSetting
                ? $registrationSetting->resolvedFormFields()
                : EmployeeRegistrationSetting::defaultFormFields();
            $departments = department::where('company_id', $selectedCompany->id)
                ->orderBy('department_name')
                ->get(['id', 'department_name']);
            $officeShifts = office_shift::where('company_id', $selectedCompany->id)
                ->orderBy('shift_name')
                ->get(['id', 'shift_name']);
        } else {
            $registrationSetting = null;
            $formFields = EmployeeRegistrationSetting::defaultFormFields();
            $departments = collect();
            $officeShifts = collect();
            $companies = company::query()
                ->select('id', 'company_name', 'registration_slug')
                ->orderBy('company_name')
                ->get()
                ->each(fn (company $c) => $c->ensureRegistrationSlug());
        }

        $orgSettings = [
            'show_department' => (bool) ($registrationSetting?->allow_department_selection ?? true),
            'show_designation' => (bool) ($registrationSetting?->allow_designation_selection ?? true),
            'show_shift' => (bool) ($registrationSetting?->allow_shift_selection ?? false),
        ];

        if ($selectedCompany) {
            $orgSettings['show_department'] = true;
            $orgSettings['show_designation'] = true;
        }

        return view('employee_registration.form', compact(
            'companies',
            'general_setting',
            'selectedCompany',
            'registrationSetting',
            'formFields',
            'departments',
            'officeShifts',
            'orgSettings',
            'registrationEnabledCompanyIds'
        ));
    }

    public function config(string $companyKey)
    {
        $company = $this->resolveCompanyFromKey($companyKey);

        if (! $company) {
            return response()->json(['error' => __('Company not found.')], 404);
        }

        $companyId = (int) $company->id;
        $setting = EmployeeRegistrationSetting::where('company_id', $companyId)
            ->where('is_enabled', true)
            ->first();

        if (! $setting) {
            return response()->json(['error' => __('Registration is not enabled for this company.')], 404);
        }

        $company->ensureRegistrationSlug();

        return response()->json([
            'company_name' => $company->company_name ?? '',
            'setting' => [
                'company_id' => $setting->company_id,
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
        $companyId = (int) $request->company_id;
        if (! $this->registrationEnabled($companyId)) {
            return response('', 403);
        }

        $rows = department::where('company_id', $companyId)->select('id', 'department_name')->get();
        $output = '<option value=""></option>';
        foreach ($rows as $row) {
            $output .= '<option value="'.$row->id.'">'.$row->department_name.'</option>';
        }

        return $output;
    }

    public function designations(Request $request)
    {
        $departmentId = (int) $request->department_id;
        $department = department::find($departmentId);
        if (! $department || ! $this->registrationEnabled((int) $department->company_id)) {
            return response('', 403);
        }

        $rows = designation::where('department_id', $departmentId)->select('id', 'designation_name')->get();
        $output = '<option value=""></option>';
        foreach ($rows as $row) {
            $output .= '<option value="'.$row->id.'">'.$row->designation_name.'</option>';
        }

        return $output;
    }

    public function shifts(Request $request)
    {
        $companyId = (int) $request->company_id;
        if (! $this->registrationEnabled($companyId)) {
            return response('', 403);
        }

        $rows = office_shift::where('company_id', $companyId)->select('id', 'shift_name')->get();
        $output = '<option value=""></option>';
        foreach ($rows as $row) {
            $output .= '<option value="'.$row->id.'">'.$row->shift_name.'</option>';
        }

        return $output;
    }

    public function store(Request $request)
    {
        $companyId = (int) $request->company_id;
        $setting = EmployeeRegistrationSetting::where('company_id', $companyId)
            ->where('is_enabled', true)
            ->first();

        if (! $setting) {
            return response()->json(['error' => __('Registration is not enabled for this company.')], 403);
        }

        $fields = $setting->resolvedFormFields();
        $rules = ['company_id' => 'required|exists:companies,id'];
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

        if (! $officeShiftId) {
            $officeShiftId = (int) office_shift::where('company_id', $companyId)->value('id');
        }

        if (! $departmentId || ! $designationId || ! $officeShiftId) {
            return response()->json(['error' => __('Company registration defaults are not configured. Please contact administrator.')], 422);
        }

        $department = department::where('id', $departmentId)->where('company_id', $companyId)->first();
        $designation = designation::where('id', $designationId)->where('department_id', $departmentId)->first();
        $shift = office_shift::where('id', $officeShiftId)->where('company_id', $companyId)->first();

        if (! $department || ! $designation || ! $shift) {
            return response()->json(['error' => __('Invalid department, designation or shift for selected company.')], 422);
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
            : now()->format(env('Date_Format', 'Y-m-d'));

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth ?? now()->subYears(18)->format(env('Date_Format', 'Y-m-d')),
            'gender' => $request->gender,
            'department_id' => $departmentId,
            'company_id' => $companyId,
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
            $this->allLeaveTypeDataNewlyStore($employee);

            if (Schema::hasTable('employee_activity_logs')) {
                EmployeeActivityLog::create([
                    'employee_id' => $employee->id,
                    'performed_by' => null,
                    'action' => 'employee.self_registered',
                    'description' => 'Employee registered via public form.',
                    'meta' => ['company_id' => $companyId, 'auto_approve' => $setting->auto_approve],
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
            Log::error('Public employee registration failed', [
                'company_id' => $companyId,
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
            ?: ($setting->auto_approve
                ? __('Registration successful. Login credentials have been sent to your email.')
                : __('Registration submitted. Admin will review your account. Login credentials will be emailed after approval.'));

        if (! $emailSent) {
            $message .= ' '.__('We could not send the login email. Please contact your administrator for your password.');
        }

        return response()->json([
            'success' => $message,
            'staff_id' => $employee->staff_id,
            'email_sent' => $emailSent,
        ]);
    }

    private function registrationEnabled(int $companyId): bool
    {
        return EmployeeRegistrationSetting::where('company_id', $companyId)
            ->where('is_enabled', true)
            ->exists();
    }

    private function resolveCompanyFromKey(string $key): ?company
    {
        if (ctype_digit($key)) {
            return company::select('id', 'company_name', 'registration_slug')->find((int) $key);
        }

        return company::findByRegistrationSlug($key);
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
