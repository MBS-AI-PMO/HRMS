<?php

namespace App\Http\Controllers;

use App\Models\company;
use App\Models\department;
use App\Models\designation;
use App\Models\EmployeeRegistrationSetting;
use App\Models\office_shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class EmployeeRegistrationSettingController extends Controller
{
    public function index()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if (! auth()->user()->can('customize-setting')) {
            return abort(403, __('You are not authorized'));
        }

        $migrationRequired = ! Schema::hasTable('employee_registration_settings');
        $rows = collect();

        if (! $migrationRequired) {
            $companies = company::select('id', 'company_name')->orderBy('company_name')->get();
            foreach ($companies as $company) {
                EmployeeRegistrationSetting::forCompany((int) $company->id);
            }

            $settingsByCompany = EmployeeRegistrationSetting::query()
                ->get()
                ->keyBy('company_id');

            $rows = $companies->map(function ($company) use ($settingsByCompany) {
                $setting = $settingsByCompany->get($company->id);
                $companyModel = company::find($company->id);
                if ($companyModel) {
                    $companyModel->ensureRegistrationSlug();
                }

                return (object) [
                    'company_id' => $company->id,
                    'company_name' => $company->company_name,
                    'registration_url' => EmployeeRegistrationSetting::registrationUrl((int) $company->id),
                    'is_enabled' => (bool) ($setting->is_enabled ?? false),
                    'page_title' => $setting->page_title ?? null,
                    'auto_approve' => (bool) ($setting->auto_approve ?? false),
                    'default_attendance_type' => $setting->default_attendance_type ?? 'location_based',
                    'updated_at' => $setting->updated_at ?? null,
                ];
            });
        }

        return view('settings.employee_registration.index', compact('rows', 'migrationRequired'));
    }

    public function edit(int $companyId)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if (! auth()->user()->can('customize-setting')) {
            return abort(403, __('You are not authorized'));
        }

        if (! Schema::hasTable('employee_registration_settings')) {
            return redirect()
                ->route('employee_registration_settings.index')
                ->with('error', __('Please run database migration first: php artisan migrate'));
        }

        $company = company::findOrFail($companyId);
        EmployeeRegistrationSetting::forCompany($companyId);
        $roles = Role::where('is_active', 1)->select('id', 'name')->orderBy('name')->get();

        return view('settings.employee_registration.edit', compact('company', 'roles'));
    }

    public function companyData(int $companyId)
    {
        if (! auth()->check() || ! auth()->user()->can('customize-setting')) {
            return response()->json(['error' => __('You are not authorized')], 403);
        }

        if (! Schema::hasTable('employee_registration_settings')) {
            return response()->json(['error' => __('Please run database migration first: php artisan migrate')], 500);
        }

        company::findOrFail($companyId);
        $setting = EmployeeRegistrationSetting::forCompany($companyId);

        return response()->json([
            'setting' => $setting,
            'form_fields' => $setting->resolvedFormFields(),
            'departments' => department::where('company_id', $companyId)->select('id', 'department_name')->get(),
            'designations' => designation::where('company_id', $companyId)->select('id', 'designation_name', 'department_id')->get(),
            'shifts' => office_shift::where('company_id', $companyId)->select('id', 'shift_name')->get(),
        ]);
    }

    public function update(Request $request, int $companyId)
    {
        if (! auth()->check() || ! auth()->user()->can('customize-setting')) {
            return response()->json(['error' => __('You are not authorized')], 403);
        }

        if (! Schema::hasTable('employee_registration_settings')) {
            return response()->json(['error' => __('Please run database migration first: php artisan migrate')], 500);
        }

        company::findOrFail($companyId);
        $setting = EmployeeRegistrationSetting::forCompany($companyId);

        $validator = Validator::make($request->all(), [
            'is_enabled' => 'nullable|boolean',
            'page_title' => 'nullable|string|max:191',
            'intro_text' => 'nullable|string|max:5000',
            'success_message' => 'nullable|string|max:1000',
            'allow_department_selection' => 'nullable|boolean',
            'allow_designation_selection' => 'nullable|boolean',
            'allow_shift_selection' => 'nullable|boolean',
            'default_department_id' => 'nullable|exists:departments,id',
            'default_designation_id' => 'nullable|exists:designations,id',
            'default_office_shift_id' => 'nullable|exists:office_shifts,id',
            'default_role_users_id' => 'nullable|exists:roles,id',
            'default_attendance_type' => 'nullable|in:general,location_based',
            'auto_approve' => 'nullable|boolean',
            'form_fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $formFields = $setting->resolvedFormFields();
        if ($request->has('form_fields') && is_array($request->form_fields)) {
            foreach ($formFields as $key => $config) {
                if (isset($request->form_fields[$key])) {
                    $formFields[$key]['enabled'] = ! empty($request->form_fields[$key]['enabled']);
                    $formFields[$key]['required'] = ! empty($request->form_fields[$key]['required']);
                }
            }
        }

        foreach (['first_name', 'last_name', 'username', 'contact_no', 'email'] as $requiredField) {
            $formFields[$requiredField]['enabled'] = true;
            $formFields[$requiredField]['required'] = true;
        }

        $setting->update([
            'is_enabled' => $request->boolean('is_enabled'),
            'page_title' => $request->page_title,
            'intro_text' => $request->intro_text,
            'success_message' => $request->success_message,
            'allow_department_selection' => $request->boolean('allow_department_selection'),
            'allow_designation_selection' => $request->boolean('allow_designation_selection'),
            'allow_shift_selection' => $request->boolean('allow_shift_selection'),
            'default_department_id' => $request->default_department_id,
            'default_designation_id' => $request->default_designation_id,
            'default_office_shift_id' => $request->default_office_shift_id,
            'default_role_users_id' => $request->default_role_users_id ?: 3,
            'default_attendance_type' => $request->default_attendance_type ?: 'location_based',
            'auto_approve' => $request->boolean('auto_approve'),
            'form_fields' => $formFields,
        ]);

        return response()->json([
            'success' => __('Data is successfully updated'),
            'public_url' => EmployeeRegistrationSetting::registrationUrl($companyId),
        ]);
    }
}
