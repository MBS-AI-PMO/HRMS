<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientRegistrationSetting;
use App\Models\Department;
use App\Models\Designation;
use App\Models\OfficeShift;
use App\Models\Project;
use App\Support\CompanyScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class ClientRegistrationSettingController extends Controller
{
    public function index()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if (! auth()->user()->can('customize-setting')) {
            return abort(403, __('You are not authorized'));
        }

        $migrationRequired = ! Schema::hasTable('client_registration_settings');
        $rows = collect();

        if (! $migrationRequired) {
            $settings = ClientRegistrationSetting::query()
                ->orderByDesc('updated_at')
                ->get();

            $clients = Client::query()
                ->select('id', 'company_name', 'first_name', 'last_name')
                ->whereIn('id', $settings->pluck('client_id')->unique())
                ->get()
                ->keyBy('id');

            $projects = Project::query()
                ->select('id', 'title')
                ->whereIn('id', $settings->flatMap(fn ($s) => $s->resolvedProjectIds())->unique())
                ->get()
                ->keyBy('id');

            $rows = $settings->map(function (ClientRegistrationSetting $setting) use ($clients, $projects) {
                $setting->ensureRegistrationSlug();
                $client = $clients->get($setting->client_id);
                $projectNames = collect($setting->resolvedProjectIds())
                    ->map(fn ($id) => $projects->get($id)?->title)
                    ->filter()
                    ->values();

                return (object) [
                    'id' => $setting->id,
                    'client_id' => $setting->client_id,
                    'client_name' => $client
                        ? trim(($client->company_name ?: '').' '.($client->first_name ?? '').' '.($client->last_name ?? ''))
                        : __('Unknown client'),
                    'label' => $setting->label,
                    'project_names' => $projectNames,
                    'registration_url' => $setting->registrationUrl(),
                    'is_enabled' => (bool) $setting->is_enabled,
                    'page_title' => $setting->page_title,
                    'auto_approve' => (bool) $setting->auto_approve,
                    'updated_at' => $setting->updated_at,
                ];
            });
        }

        return view('settings.client_registration.index', compact('rows', 'migrationRequired'));
    }

    public function create()
    {
        if (! auth()->check() || ! auth()->user()->can('customize-setting')) {
            return abort(403, __('You are not authorized'));
        }

        if (! Schema::hasTable('client_registration_settings')) {
            return redirect()
                ->route('client_registration_settings.index')
                ->with('error', __('Please run database migration first: php artisan migrate'));
        }

        $clients = Client::query()
            ->select('id', 'company_name', 'first_name', 'last_name')
            ->orderBy('company_name')
            ->orderBy('first_name')
            ->get();

        $roles = Role::where('is_active', 1)->select('id', 'name')->orderBy('name')->get();

        return view('settings.client_registration.edit', [
            'setting' => new ClientRegistrationSetting(['is_enabled' => false]),
            'clients' => $clients,
            'roles' => $roles,
            'isNew' => true,
        ]);
    }

    public function edit(int $id)
    {
        if (! auth()->check() || ! auth()->user()->can('customize-setting')) {
            return abort(403, __('You are not authorized'));
        }

        if (! Schema::hasTable('client_registration_settings')) {
            return redirect()
                ->route('client_registration_settings.index')
                ->with('error', __('Please run database migration first: php artisan migrate'));
        }

        $setting = ClientRegistrationSetting::findOrFail($id);
        $setting->ensureRegistrationSlug();

        $clients = Client::query()
            ->select('id', 'company_name', 'first_name', 'last_name')
            ->orderBy('company_name')
            ->orderBy('first_name')
            ->get();

        $roles = Role::where('is_active', 1)->select('id', 'name')->orderBy('name')->get();

        return view('settings.client_registration.edit', [
            'setting' => $setting,
            'clients' => $clients,
            'roles' => $roles,
            'isNew' => false,
        ]);
    }

    public function data(int $id)
    {
        if (! auth()->check() || ! auth()->user()->can('customize-setting')) {
            return response()->json(['error' => __('You are not authorized')], 403);
        }

        if (! Schema::hasTable('client_registration_settings')) {
            return response()->json(['error' => __('Please run database migration first: php artisan migrate')], 500);
        }

        $setting = ClientRegistrationSetting::findOrFail($id);
        $companyId = CompanyScope::resolveCompanyIdForClient((int) $setting->client_id);

        return response()->json([
            'setting' => $setting,
            'form_fields' => $setting->resolvedFormFields(),
            'projects' => Project::query()
                ->where('client_id', $setting->client_id)
                ->orderBy('title')
                ->get(['id', 'title', 'project_status']),
            'departments' => $companyId
                ? Department::where('company_id', $companyId)->select('id', 'department_name')->get()
                : [],
            'designations' => $companyId
                ? Designation::where('company_id', $companyId)->select('id', 'designation_name', 'department_id')->get()
                : [],
            'shifts' => $companyId
                ? OfficeShift::where('company_id', $companyId)->select('id', 'shift_name')->get()
                : [],
            'company_id' => $companyId,
        ]);
    }

    public function clientData(int $clientId)
    {
        if (! auth()->check() || ! auth()->user()->can('customize-setting')) {
            return response()->json(['error' => __('You are not authorized')], 403);
        }

        Client::findOrFail($clientId);
        $companyId = CompanyScope::resolveCompanyIdForClient($clientId);

        return response()->json([
            'company_id' => $companyId,
            'projects' => Project::query()
                ->where('client_id', $clientId)
                ->orderBy('title')
                ->get(['id', 'title', 'project_status']),
            'departments' => $companyId
                ? Department::where('company_id', $companyId)->select('id', 'department_name')->get()
                : [],
            'designations' => $companyId
                ? Designation::where('company_id', $companyId)->select('id', 'designation_name', 'department_id')->get()
                : [],
            'shifts' => $companyId
                ? OfficeShift::where('company_id', $companyId)->select('id', 'shift_name')->get()
                : [],
        ]);
    }

    public function store(Request $request)
    {
        if (! auth()->check() || ! auth()->user()->can('customize-setting')) {
            return response()->json(['error' => __('You are not authorized')], 403);
        }

        if (! Schema::hasTable('client_registration_settings')) {
            return response()->json(['error' => __('Please run database migration first: php artisan migrate')], 500);
        }

        $validator = $this->validator($request);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $clientId = (int) $request->client_id;
        $projectIds = $this->validatedProjectIds($request, $clientId);

        if ($projectIds === []) {
            return response()->json(['errors' => [__('Please select at least one project for this registration link.')]], 422);
        }

        $setting = ClientRegistrationSetting::create([
            'client_id' => $clientId,
            'label' => $request->label,
            'project_ids' => $projectIds,
            'registration_slug' => ClientRegistrationSetting::makeUniqueRegistrationSlug(
                (string) ($request->label ?: 'client-'.$clientId)
            ),
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
            'form_fields' => $this->buildFormFields($request, new ClientRegistrationSetting),
        ]);

        return response()->json([
            'success' => __('Data is successfully saved'),
            'redirect' => route('client_registration_settings.edit', $setting->id),
            'public_url' => $setting->registrationUrl(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        if (! auth()->check() || ! auth()->user()->can('customize-setting')) {
            return response()->json(['error' => __('You are not authorized')], 403);
        }

        if (! Schema::hasTable('client_registration_settings')) {
            return response()->json(['error' => __('Please run database migration first: php artisan migrate')], 500);
        }

        $setting = ClientRegistrationSetting::findOrFail($id);

        $validator = $this->validator($request);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $clientId = (int) $request->client_id;
        $projectIds = $this->validatedProjectIds($request, $clientId);

        if ($projectIds === []) {
            return response()->json(['errors' => [__('Please select at least one project for this registration link.')]], 422);
        }

        $setting->update([
            'client_id' => $clientId,
            'label' => $request->label,
            'project_ids' => $projectIds,
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
            'form_fields' => $this->buildFormFields($request, $setting),
        ]);

        $setting->ensureRegistrationSlug();

        return response()->json([
            'success' => __('Data is successfully updated'),
            'public_url' => $setting->registrationUrl(),
        ]);
    }

    public function destroy(int $id)
    {
        if (! auth()->check() || ! auth()->user()->can('customize-setting')) {
            return response()->json(['error' => __('You are not authorized')], 403);
        }

        ClientRegistrationSetting::findOrFail($id)->delete();

        return response()->json(['success' => __('Deleted successfully')]);
    }

    private function validator(Request $request)
    {
        return Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'label' => 'nullable|string|max:191',
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
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'integer|exists:projects,id',
            'form_fields' => 'nullable|array',
        ]);
    }

    private function validatedProjectIds(Request $request, int $clientId): array
    {
        return Project::query()
            ->where('client_id', $clientId)
            ->whereIn('id', collect($request->input('project_ids', []))->map(fn ($id) => (int) $id)->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function buildFormFields(Request $request, ClientRegistrationSetting $setting): array
    {
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

        return $formFields;
    }
}
