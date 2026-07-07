<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\company;
use App\Models\department;
use App\Models\location;
use App\Models\office_shift;
use App\Scopes\AuthCompanyLocationScope;
use App\Scopes\AuthCompanyScope;
use App\Support\ClientDisplay;
use App\Support\CompanyScope;
use App\Support\ManagedEmployeeScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

use Spatie\Permission\Models\Role;


class LocationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'secure.app']);
        $this->middleware('throttle:90,1')->only([
            'store',
            'update',
            'assignShift',
            'updateLocationAttendanceType',
            'delete',
            'delete_by_selection',
        ]);
    }

    protected function canEditLocation(int $locationId): bool
    {
        $user = auth()->user();

        if ($user->can('edit-location')) {
            return true;
        }

        return location::userCanManageLocationForMyPage((int) $user->id, $locationId);
    }

    protected function canAssignShiftAtLocation(int $locationId): bool
    {
        $user = auth()->user();

        if ($user->can('view-location')) {
            return true;
        }

        return location::userCanManageLocationForMyPage((int) $user->id, $locationId);
    }

    protected function isScopedLocationManager(): bool
    {
        $user = auth()->user();

        return ! $user->can('edit-location')
            && location::userCanAccessMyLocationsPage((int) $user->id);
    }

	public function index(Request $request)
	{
        if (! auth()->user()->can('view-location')) {
            abort(403, __('You are not authorized'));
        }

		if (request()->ajax()) {
            try {
                $relations = array_merge(['country:id,name'], $this->locationListRelations());

                $locationsQuery = location::with($relations)->latest();

                if ($request->filled('filter_company_id')) {
                    $companyId = (int) $request->filter_company_id;
                    $locationsQuery->where(function ($query) use ($companyId) {
                        $query->whereHas('companies', function ($companyQuery) use ($companyId) {
                            $companyQuery->where('companies.id', $companyId);
                        })->orWhereHas('client', function ($clientQuery) use ($companyId) {
                            $clientQuery->whereIn('clients.id', $this->clientIdsForCompany($companyId));
                        });
                    });
                }

                if ($request->filled('filter_client_id')) {
                    $locationsQuery->where('client_id', (int) $request->filter_client_id);
                }

                if ($request->filled('filter_project_id')) {
                    $projectId = (int) $request->filter_project_id;
                    $locationsQuery->whereHas('employees', function ($employeeQuery) use ($projectId) {
                        $employeeQuery->whereHas('projects', function ($projectQuery) use ($projectId) {
                            $projectQuery->where('projects.id', $projectId);
                        });
                    });
                }

                if ($request->filled('filter_department_id')) {
                    $locationsQuery->whereHas('employees', function ($employeeQuery) use ($request) {
                        $employeeQuery->where('department_id', (int) $request->filter_department_id);
                    });
                }

                if ($request->filled('filter_employee_id')) {
                    $locationsQuery->where(function ($query) use ($request) {
                        $employeeId = (int) $request->filter_employee_id;
                        $query->whereHas('employees', function ($employeeQuery) use ($employeeId) {
                            $employeeQuery->where('employees.id', $employeeId);
                        })->orWhereHas('locationHeads', function ($headQuery) use ($employeeId) {
                            $headQuery->where('employees.id', $employeeId);
                        });
                    });
                }

                return datatables()->of($locationsQuery->get())
                    ->addColumn('country', function ($row) {
                        return optional($row->country)->name ?? '';
                    })
                    ->addColumn('location_head', function ($row) {
                        return $row->locationHeadsLabel();
				})
                ->addColumn('companies', function ($row) {
                        return $this->locationClientLabel($row);
                })
                    ->addColumn('action', function ($data) {
					$button = '';
                        if (auth()->user()->can('edit-location')) {
                            $button = '<button type="button" name="edit" id="'.$data->id.'" class="edit btn btn-primary btn-sm"><i class="dripicons-pencil"></i></button>';
                            $button .= '&nbsp;&nbsp;';
                        }
                        if (auth()->user()->can('view-location')) {
                            $button .= '<button type="button" name="assign_shift" id="'.$data->id.'" class="assign_shift btn btn-info btn-sm" title="'.__('Assign Shift').'"><i class="fa fa-clock-o"></i></button>';
						$button .= '&nbsp;&nbsp;';
					}
                        if (auth()->user()->can('delete-location')) {
                            $button .= '<button type="button" name="delete" id="'.$data->id.'" class="delete btn btn-danger btn-sm"><i class="dripicons-trash"></i></button>';
					}

					return $button;
				})
				->rawColumns(['action'])
				->make(true);
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'error' => __('Unable to load locations. Please refresh and try again.'),
                    'message' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }
        }

		$countries = \DB::table('countries')->select('id','name')->get();
        $companies = CompanyScope::companiesForSelect();
        $locationHeadEmployees = $this->solochoicezEmployeesForSelect();

		return view('organization.location.index', compact('countries', 'companies', 'locationHeadEmployees'));
	}


	public function store(Request $request)
	{
		$logged_user = auth()->user();

        if (! $logged_user || ! $logged_user->can('store-location')) {
            return response()->json(['errors' => [__('You are not authorized')]], 403);
        }

            if ($request->filled('client_id')) {
                $request->merge(['owner_type' => 'client']);
            } elseif (! $request->filled('owner_type')) {
                $request->merge(['owner_type' => 'company']);
            }

			$validator = Validator::make($request->only('location_name', 'location_head', 'address1', 'address2', 'city',
				'state', 'country', 'zip', 'latitude', 'longitude', 'max_radius', 'owner_type', 'owner_company_id', 'client_id'),
				array_merge([
					'location_name' => 'required|unique:locations,location_name,',
					'address1' => 'required',
					'zip' => 'nullable|numeric',
					'country'=> 'required',
                    'latitude' => 'nullable|numeric|between:-90,90',
                    'longitude' => 'nullable|numeric|between:-180,180',
                    'max_radius' => 'nullable|numeric|min:0',
                    'owner_type' => 'required|in:company,client',
                    'location_head_ids' => 'nullable|array',
                    'location_head_ids.*' => 'exists:employees,id',
				], $this->ownerFieldValidationRules($request))
			);


			if ($validator->fails())
			{
				return response()->json(['errors' => $validator->errors()->all()]);
			}

            $headErrors = $this->validateClientSelection($request);

            if (! empty($headErrors)) {
                return response()->json(['errors' => $headErrors]);
            }

			$data = [];

			$data['location_name'] = $request->location_name;
			$locationHeadIds = $this->normalizeLocationHeadIds($request);
			$data['location_head'] = $locationHeadIds[0] ?? null;
			$data ['address1'] = $request->address1;
			$data ['address2'] = $request->address2;
			$data ['city'] = $request->city;
			$data ['state'] = $request->state;
			$data ['country'] = $request->country;
			$data ['zip'] = $request->zip;


            $data['latitude'] = $request->latitude !== '' ? $request->latitude : null;
            $data['longitude'] = $request->longitude !== '' ? $request->longitude : null;
            $data['max_radius'] = $request->max_radius !== '' ? $request->max_radius : null;

            DB::beginTransaction();
            try {
                $location = location::create($data);
                $this->applyLocationOwner(
                    $location,
                    (string) $request->owner_type,
                    $request->filled('owner_company_id') ? (int) $request->owner_company_id : null,
                    $request->filled('client_id') ? (int) $request->client_id : null
                );
                $location->locationHeads()->sync($locationHeadIds);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json(['errors' => [$e->getMessage()]]);
            }

			return response()->json(['success' => __('Data Added successfully.')]);
	}


	public function edit($id)
	{

		if(request()->ajax())
		{
            if (! $this->canEditLocation((int) $id)) {
                return response()->json(['errors' => [__('You are not authorized')]], 403);
            }

			$data = location::withoutGlobalScope(AuthCompanyLocationScope::class)->findOrFail($id);
            $owner = $this->resolveLocationOwner($data);

			return response()->json([
                'data' => $data,
                'owner_type' => $owner['type'],
                'owner_company_id' => $owner['company_id'],
                'client_id' => $owner['client_id'],
                'location_head_ids' => $data->locationHeads()->pluck('employees.id')->toArray(),
            ]);
		}
	}






	public function update(Request $request)
	{
        $id = (int) $request->hidden_id;

        if (! $this->canEditLocation($id)) {
            return response()->json(['success' => __('You are not authorized')]);
        }

        $location = location::withoutGlobalScope(AuthCompanyLocationScope::class)->findOrFail($id);

        if ($this->isScopedLocationManager()) {
            $owner = $this->resolveLocationOwner($location);
            $request->merge([
                'owner_type' => $owner['type'],
                'owner_company_id' => $owner['company_id'],
                'client_id' => $owner['client_id'],
                'location_head_ids' => $location->locationHeads()->pluck('employees.id')->toArray(),
            ]);
        }

        if ($this->isScopedLocationManager() && ! location::userCanManageLocationForMyPage((int) auth()->id(), $id)) {
            return response()->json(['errors' => [__('You are not authorized')]], 403);
        }

        if (! $this->isScopedLocationManager()) {
            if ($request->filled('client_id')) {
                $request->merge(['owner_type' => 'client']);
            } elseif (! $request->filled('owner_type')) {
                $request->merge(['owner_type' => 'company']);
            }
        }

			$data = $request->only('location_name', 'location_head', 'address1', 'address2', 'city',
				'state', 'country', 'zip', 'latitude', 'longitude', 'max_radius');

			$locationHeadIds = $this->normalizeLocationHeadIds($request);
			$data['location_head'] = $locationHeadIds[0] ?? null;
            if (($data['latitude'] ?? null) === '') {
                $data['latitude'] = null;
            }
            if (($data['longitude'] ?? null) === '') {
                $data['longitude'] = null;
            }
            if (($data['max_radius'] ?? null) === '') {
                $data['max_radius'] = null;
            }


			$validator = Validator::make($request->only('location_name', 'location_head', 'address1', 'address2', 'city',
				'state', 'country', 'zip', 'latitude', 'longitude', 'max_radius', 'owner_type', 'owner_company_id', 'client_id'),
				array_merge([
					'location_name' => 'required|unique:locations,location_name,' . $id,
					'location_head' => 'nullable',
					'address1' => 'required',
					'zip' => 'nullable|numeric',
					'country'=> 'required',
                    'latitude' => 'nullable|numeric|between:-90,90',
                    'longitude' => 'nullable|numeric|between:-180,180',
                    'max_radius' => 'nullable|numeric|min:0',
                    'owner_type' => 'required|in:company,client',
                    'location_head_ids' => 'nullable|array',
                    'location_head_ids.*' => 'exists:employees,id',
				], $this->ownerFieldValidationRules($request))
			);



			if ($validator->fails())
			{
				return response()->json(['errors'=>$validator->errors()->all()]);
			}

            if (! $this->isScopedLocationManager()) {
                $clientErrors = $this->validateClientSelection($request);

                if (! empty($clientErrors)) {
                    return response()->json(['errors' => $clientErrors]);
                }
            }

            DB::beginTransaction();
            try {
                location::withoutGlobalScope(AuthCompanyLocationScope::class)->whereId($id)->update($data);
                $location = location::withoutGlobalScope(AuthCompanyLocationScope::class)->findOrFail($id);
                if (! $this->isScopedLocationManager()) {
                    $this->applyLocationOwner(
                        $location,
                        (string) $request->owner_type,
                        $request->filled('owner_company_id') ? (int) $request->owner_company_id : null,
                        $request->filled('client_id') ? (int) $request->client_id : null
                    );
                    $location->locationHeads()->sync($locationHeadIds);
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json(['errors' => [$e->getMessage()]]);
            }

			return response()->json(['success' => __('Data is successfully updated')]);
	}

    public function clientsSelect()
    {
        if (! auth()->user()->can('view-location')) {
            return response()->json(['clients' => []], 403);
        }

        return response()->json([
            'clients' => $this->clientsForSelect()->map(function ($client) {
                return [
                    'id' => (int) $client->id,
                    'label' => $this->clientSelectLabel($client),
                ];
            })->values(),
        ]);
    }

    public function hierarchyClients(Request $request)
    {
        if (! auth()->user()->can('view-location')) {
            return response()->json(['items' => []], 403);
        }

        $companyId = CompanyScope::resolveCompanyIdForInput((int) $request->get('company_id'));

        if (! $companyId) {
            return response()->json(['items' => []]);
        }

        $clientIds = $this->clientIdsForCompany($companyId);

        $items = Client::query()
            ->select('id', 'company_name', 'first_name', 'last_name')
            ->whereIn('id', $clientIds)
            ->orderBy('company_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($client) => [
                'id' => (int) $client->id,
                'label' => $this->clientSelectLabel($client),
            ])
            ->values();

        return response()->json(['items' => $items]);
    }

    public function hierarchyProjects(Request $request)
    {
        if (! auth()->user()->can('view-location')) {
            return response()->json(['items' => []], 403);
        }

        $companyId = CompanyScope::resolveCompanyIdForInput((int) $request->get('company_id'));
        $clientId = (int) $request->get('client_id');

        if (! $companyId || ! $clientId) {
            return response()->json(['items' => []]);
        }

        $items = Project::query()
            ->select('id', 'title', 'department_id', 'project_status')
            ->where('company_id', $companyId)
            ->where('client_id', $clientId)
            ->orderBy('title')
            ->get()
            ->map(fn ($project) => [
                'id' => (int) $project->id,
                'label' => $project->title,
                'department_id' => $project->department_id ? (int) $project->department_id : null,
                'status' => $project->project_status,
            ])
            ->values();

        return response()->json(['items' => $items]);
    }

    public function hierarchyDepartments(Request $request)
    {
        if (! auth()->user()->can('view-location')) {
            return response()->json(['items' => []], 403);
        }

        $companyId = CompanyScope::resolveCompanyIdForInput((int) $request->get('company_id'));
        $clientId = (int) $request->get('client_id');
        $projectId = (int) $request->get('project_id');

        if (! $companyId) {
            return response()->json(['items' => []]);
        }

        $departmentIds = collect();

        if ($projectId) {
            $projectDepartmentId = Project::query()
                ->where('id', $projectId)
                ->value('department_id');

            if ($projectDepartmentId) {
                $departmentIds->push((int) $projectDepartmentId);
            }
        }

        if ($clientId) {
            $departmentIds = $departmentIds->merge(
                Employee::query()
                    ->where('company_id', $companyId)
                    ->where('client_id', $clientId)
                    ->whereNotNull('department_id')
                    ->distinct()
                    ->pluck('department_id')
            )->merge(
                Project::query()
                    ->where('company_id', $companyId)
                    ->where('client_id', $clientId)
                    ->whereNotNull('department_id')
                    ->distinct()
                    ->pluck('department_id')
            );
        }

        $departmentsQuery = department::query()
            ->select('id', 'department_name')
            ->where('company_id', $companyId)
            ->orderBy('department_name');

        if ($departmentIds->filter()->isNotEmpty()) {
            $departmentsQuery->whereIn('id', $departmentIds->filter()->unique()->values());
        }

        $items = $departmentsQuery->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'label' => $row->department_name,
        ])->values();

        return response()->json(['items' => $items]);
    }

    public function hierarchyEmployees(Request $request)
    {
        if (! auth()->user()->can('view-location')) {
            return response()->json(['items' => []], 403);
        }

        $companyId = CompanyScope::resolveCompanyIdForInput((int) $request->get('company_id'));
        $clientId = (int) $request->get('client_id');
        $projectId = (int) $request->get('project_id');
        $departmentId = (int) $request->get('department_id');

        if (! $companyId) {
            return response()->json(['items' => []]);
        }

        $employeesQuery = Employee::query()
            ->select('id', 'first_name', 'last_name', 'staff_id', 'department_id', 'client_id')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->whereNull('exit_date')
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($clientId) {
            $employeesQuery->where('client_id', $clientId);
        }

        if ($departmentId) {
            $employeesQuery->where('department_id', $departmentId);
        }

        if ($projectId) {
            $employeesQuery->whereHas('projects', function ($query) use ($projectId) {
                $query->where('projects.id', $projectId);
            });
        }

        $items = $employeesQuery->get()->map(fn ($employee) => [
            'id' => (int) $employee->id,
            'label' => trim($employee->full_name.($employee->staff_id ? ' ('.$employee->staff_id.')' : '')),
        ])->values();

        return response()->json(['items' => $items]);
	}

    public function employeesByCompanies(Request $request)
    {
        if (! auth()->user()->can('view-location') && ! location::userCanAccessMyLocationsPage((int) auth()->id())) {
            return response()->json(['employees' => [], 'companies' => []], 403);
        }

        $companyIds = $request->input('company_ids', []);
        if (is_string($companyIds)) {
            $companyIds = array_filter(array_map('intval', explode(',', $companyIds)));
        }

        if (empty($companyIds)) {
            return response()->json(['employees' => []]);
        }

        if ($this->isScopedLocationManager()) {
            $managedLocationIds = location::locationIdsForMyLocationsPage((int) auth()->id());
            $allowedCompanyIds = $this->companiesForLocationIds($managedLocationIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $companyIds = array_values(array_intersect($companyIds, $allowedCompanyIds));

            if ($companyIds === []) {
                return response()->json(['employees' => [], 'companies' => []]);
            }
        }

        $employees = Employee::query()
            ->select('id', 'first_name', 'last_name', 'company_id')
            ->where('is_active', 1)
            ->whereNull('exit_date')
            ->whereIn('company_id', $companyIds)
            ->orderBy('first_name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'full_name' => $item->full_name,
                    'company_id' => $item->company_id,
                ];
            });

        $shiftsByCompany = office_shift::query()
            ->select('id', 'shift_name', 'company_id')
            ->whereIn('company_id', $companyIds)
            ->orderBy('shift_name')
            ->get()
            ->groupBy('company_id');

        $companies = company::query()
            ->select('id', 'company_name')
            ->whereIn('id', $companyIds)
            ->orderBy('company_name')
            ->get()
            ->map(function ($company) use ($shiftsByCompany) {
                return [
                    'id' => $company->id,
                    'company_name' => $company->company_name,
                    'shifts' => collect($shiftsByCompany[$company->id] ?? [])->map(function ($shift) {
                        return [
                            'id' => $shift->id,
                            'shift_name' => $shift->shift_name,
                        ];
                    })->values(),
                ];
            });

        return response()->json([
            'employees' => $employees,
            'companies' => $companies,
        ]);
    }

    public function shiftAssignmentForm($id)
    {
        if (! request()->ajax()) {
            abort(404);
        }

        if (! $this->canAssignShiftAtLocation((int) $id)) {
            return response()->json(['errors' => [__('You are not authorized')]], 403);
        }

        $location = location::withoutGlobalScope(AuthCompanyLocationScope::class)
            ->with('companies:id,company_name')
            ->findOrFail($id);
        $companyIds = $location->companies->pluck('id');

        $employeesByCompany = Employee::query()
            ->select('company_id', DB::raw('count(*) as total'))
            ->where('location_id', $id)
            ->where('is_active', 1)
            ->whereNull('exit_date')
            ->when($companyIds->isNotEmpty(), function ($query) use ($companyIds) {
                $query->whereIn('company_id', $companyIds);
            })
            ->groupBy('company_id')
            ->pluck('total', 'company_id');

        $shiftsByCompany = office_shift::query()
            ->select('id', 'shift_name', 'company_id')
            ->when($companyIds->isNotEmpty(), function ($query) use ($companyIds) {
                $query->whereIn('company_id', $companyIds);
            })
            ->orderBy('shift_name')
            ->get()
            ->groupBy('company_id');

        return response()->json([
            'location' => [
                'id' => $location->id,
                'location_name' => $location->location_name,
            ],
            'companies' => $location->companies->map(function ($company) use ($employeesByCompany, $shiftsByCompany) {
                return [
                    'id' => $company->id,
                    'company_name' => $company->company_name,
                    'employee_count' => (int) ($employeesByCompany[$company->id] ?? 0),
                    'shifts' => collect($shiftsByCompany[$company->id] ?? [])->map(function ($shift) {
                        return [
                            'id' => $shift->id,
                            'shift_name' => $shift->shift_name,
                        ];
                    })->values(),
                ];
            })->values(),
            'total_employees' => (int) $employeesByCompany->sum(),
        ]);
    }

    public function assignShift(Request $request)
    {
        $locationId = (int) $request->location_id;

        if (! $this->canAssignShiftAtLocation($locationId)) {
            return response()->json(['errors' => [__('You are not authorized')]]);
        }

        $validator = Validator::make($request->all(), [
            'location_id' => 'required|exists:locations,id',
            'shifts' => 'required|array|min:1',
            'shifts.*.company_id' => 'required|exists:companies,id',
            'shifts.*.office_shift_id' => 'required|exists:office_shifts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()]);
        }

        $locationId = (int) $request->location_id;
        $location = location::withoutGlobalScope(AuthCompanyLocationScope::class)
            ->with('companies:id')
            ->findOrFail($locationId);
        $locationCompanyIds = $location->companies->pluck('id')->map(fn ($id) => (int) $id)->all();
        $totalUpdated = 0;

        DB::beginTransaction();

        try {
            $totalUpdated = $this->applyOfficeShiftsToLocation($locationId, $request, $locationCompanyIds);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['errors' => [$e->getMessage()]]);
        }

        if ($totalUpdated === 0) {
            return response()->json(['errors' => [__('No active employees were found at this location for the selected company.')]]);
        }

        return response()->json([
            'success' => __('Shift updated for :count employee(s) at this location.', ['count' => $totalUpdated]),
        ]);
    }

    public function attendanceTypeForm($id)
    {
        if (! request()->ajax()) {
            abort(404);
        }

        $locationId = (int) $id;

        if (! $this->canAssignShiftAtLocation($locationId)) {
            return response()->json(['errors' => [__('You are not authorized')]], 403);
        }

        $location = location::withoutGlobalScope(AuthCompanyLocationScope::class)
            ->select('id', 'location_name')
            ->findOrFail($locationId);

        $employeeQuery = Employee::withoutGlobalScope(AuthCompanyScope::class)
            ->where('location_id', $locationId)
            ->where('is_active', 1)
            ->whereNull('exit_date');

        $summary = (clone $employeeQuery)
            ->select('attendance_type', DB::raw('count(*) as total'))
            ->groupBy('attendance_type')
            ->pluck('total', 'attendance_type');

        return response()->json([
            'location' => [
                'id' => $location->id,
                'location_name' => $location->location_name,
            ],
            'total_employees' => (int) $summary->sum(),
            'attendance_summary' => [
                'general' => (int) ($summary['general'] ?? 0),
                'location_based' => (int) ($summary['location_based'] ?? 0),
            ],
        ]);
    }

    public function updateLocationAttendanceType(Request $request)
    {
        $locationId = (int) $request->location_id;

        if (! $this->canAssignShiftAtLocation($locationId)) {
            return response()->json(['errors' => [__('You are not authorized')]]);
        }

        $validator = Validator::make($request->all(), [
            'location_id' => 'required|exists:locations,id',
            'attendance_type' => 'required|in:general,location_based',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()]);
        }

        $attendanceType = (string) $request->attendance_type;

        $updated = Employee::withoutGlobalScope(AuthCompanyScope::class)
            ->where('location_id', $locationId)
            ->where('is_active', 1)
            ->whereNull('exit_date')
            ->update(['attendance_type' => $attendanceType]);

        if ($updated === 0) {
            return response()->json(['errors' => [__('No active employees were found at this location.')]]);
        }

        $typeLabel = $attendanceType === 'location_based'
            ? __('Location Based')
            : __('General');

        return response()->json([
            'success' => __('Attendance type updated to :type for :count employee(s) at this location.', [
                'type' => $typeLabel,
                'count' => $updated,
            ]),
        ]);
    }

    public function myLocations()
    {
        $userId = (int) auth()->id();
        $isDataTableRequest = request()->ajax() || request()->filled('draw');

        if (! $this->userHasPermission('view-location') && ! ManagedEmployeeScope::canAccessMyLocations((int) $userId)) {
            if ($isDataTableRequest) {
                return response()->json(['error' => __('You are not authorized to manage locations.')], 403);
            }

            return abort(403, __('You are not authorized to manage locations.'));
        }

        if ($isDataTableRequest) {
            try {
                $locations = location::withoutGlobalScope(AuthCompanyLocationScope::class)
                    ->with($this->locationListRelations())
                    ->withCount([
                        'employees as active_employees_count' => function ($query) {
                            $query->withoutGlobalScope(AuthCompanyScope::class)
                                ->where('is_active', 1)
                                ->where(function ($activeQuery) {
                                    $activeQuery->whereNull('exit_date')
                                        ->orWhere('exit_date', '>=', date('Y-m-d'))
                                        ->orWhere('exit_date', '0000-00-00');
                                });
                        },
                    ]);

                if (! $this->userHasPermission('view-location')) {
                    $managedLocationIds = location::locationIdsForMyLocationsPage($userId);

                    if ($managedLocationIds === []) {
                        $locations->whereRaw('1 = 0');
                    } else {
                        $locations->whereIn('id', $managedLocationIds);
                    }
                }

                $rows = $locations->latest('id')->get()->map(function ($row) use ($userId) {
                    return [
                        'id' => $row->id,
                        'location_name' => $row->location_name,
                        'location_heads' => $row->locationHeadsLabel(),
                        'companies' => $this->locationClientLabel($row),
                        'active_employees_count' => (int) $row->active_employees_count,
                        'action' => $this->myLocationActionButtons($row, $userId),
                    ];
                });

                return datatables()->of($rows)
                    ->rawColumns(['action'])
                    ->make(true);
            } catch (\Throwable $e) {
                report($e);

                return response()->json([
                    'error' => __('Unable to load locations. Please refresh and try again.'),
                    'message' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }
        }

        $countries = \DB::table('countries')->select('id', 'name')->get();
        $locationHeadManage = ! $this->userHasPermission('edit-location');

        return view('organization.location.my', compact('countries', 'locationHeadManage'));
    }

    private function userHasPermission(string $permission): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        try {
            return $user->can($permission);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    private function myLocationActionButtons(location $row, int $userId): string
    {
        $canManage = location::userCanManageLocationForMyPage($userId, (int) $row->id);
        $canEdit = $this->userHasPermission('edit-location') || $canManage;

        if (! $canEdit) {
            return '';
        }

        $button = '<button type="button" name="edit" id="'.$row->id.'" class="edit btn btn-info btn-sm" title="'.__('Edit').'"><i class="dripicons-pencil"></i></button>';

        if ((int) $row->active_employees_count > 0) {
            $button .= '&nbsp;&nbsp;<button type="button" name="change_attendance" id="'.$row->id.'" class="change_attendance btn btn-warning btn-sm" title="'.__('Change Attendance Type').'"><i class="fa fa-exchange"></i></button>';
        }

        return $button;
    }

    private function locationListRelations(): array
    {
        $relations = [
            'companies:id,company_name',
        ];

        if (Schema::hasTable('location_heads')) {
            $relations[] = 'locationHeads:id,first_name,last_name';
        }

        if (Schema::hasColumn('locations', 'client_id') && Schema::hasTable('clients')) {
            $relations[] = 'client:id,company_name,first_name,last_name';
        }

        return $relations;
    }

    private function resolveSolochoicezCompanyId(): ?int
    {
        if (method_exists(CompanyScope::class, 'solochoicezCompanyId')) {
            return CompanyScope::solochoicezCompanyId();
        }

        $id = company::query()
            ->whereRaw('LOWER(company_name) LIKE ?', ['%solochoice%'])
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function solochoicezEmployeesForSelect()
    {
        if (method_exists(CompanyScope::class, 'solochoicezEmployeesForSelect')) {
            return CompanyScope::solochoicezEmployeesForSelect();
        }

        $companyId = $this->resolveSolochoicezCompanyId();

        if (! $companyId) {
            return collect();
        }

        return Employee::withoutGlobalScope(AuthCompanyScope::class)
            ->select('id', 'first_name', 'last_name')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->where(function ($query) {
                $query->whereNull('exit_date')
                    ->orWhere('exit_date', '>=', date('Y-m-d'))
                    ->orWhere('exit_date', '0000-00-00');
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    private function companiesForLocationIds(array $locationIds)
    {
        if (method_exists(CompanyScope::class, 'companiesForLocationIds')) {
            return CompanyScope::companiesForLocationIds($locationIds);
        }

        $locationIds = array_values(array_filter(array_map('intval', $locationIds)));

        if ($locationIds === []) {
            return collect();
        }

        $companyIds = DB::table('company_location')
            ->whereIn('location_id', $locationIds)
            ->pluck('company_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($companyIds === []) {
            return collect();
        }

        return company::withoutGlobalScopes()
            ->select('id', 'company_name')
            ->whereIn('id', $companyIds)
            ->orderBy('company_name')
            ->get();
    }

    private function ownerFieldValidationRules(Request $request): array
    {
        if ($request->input('owner_type') === 'client') {
            return [
                'client_id' => 'required|integer|exists:clients,id',
                'owner_company_id' => 'nullable|integer|exists:companies,id',
            ];
        }

        return [
            'owner_company_id' => 'required|integer|exists:companies,id',
            'client_id' => 'nullable|integer|exists:clients,id',
        ];
    }

    private function resolveLocationOwner(location $location): array
    {
        if ($location->client_id) {
            return [
                'type' => 'client',
                'company_id' => null,
                'client_id' => (int) $location->client_id,
            ];
        }

        $companyId = $location->companies()->value('companies.id');

        return [
            'type' => 'company',
            'company_id' => $companyId ? (int) $companyId : null,
            'client_id' => null,
        ];
    }

    private function applyLocationOwner(location $location, string $ownerType, ?int $companyId, ?int $clientId): void
    {
        if ($ownerType === 'client') {
            if (! $clientId) {
                throw new \RuntimeException(__('Please select a client.'));
            }

            $location->update(['client_id' => $clientId]);
            $this->syncCompaniesFromClient($location, $clientId);

            return;
        }

        $location->update(['client_id' => null]);

        if (! $companyId) {
            throw new \RuntimeException(__('Please select a company.'));
        }

        $location->companies()->sync([$companyId]);
    }

    private function clientsForSelect()
    {
        return Client::query()
            ->select('id', 'company_name', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('company_name')
            ->get();
    }

    private function clientIdsForCompany(int $companyId): array
    {
        return CompanyScope::clientIdsForCompany($companyId);
    }

    private function clientSelectLabel($client): string
    {
        return ClientDisplay::label($client);
    }

    private function locationClientLabel(location $location): string
    {
        if ($location->client_id && $location->client) {
            return __('Client').': '.ClientDisplay::label($location->client);
        }

        $companyNames = $location->companies->pluck('company_name')->filter();

        if ($companyNames->isNotEmpty()) {
            return __('Company').': '.$companyNames->implode(', ');
        }

        return '---';
    }

    private function resolveClientIdFromLocation(location $location): ?int
    {
        if ($location->client_id) {
            return (int) $location->client_id;
        }

        $companyName = $location->companies()->value('company_name');

        if (! $companyName) {
            return null;
        }

        $clientId = Client::query()
            ->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower(trim((string) $companyName))])
            ->value('id');

        return $clientId ? (int) $clientId : null;
    }

    private function syncCompaniesFromClient(location $location, int $clientId): void
    {
        $client = Client::query()->findOrFail($clientId);
        $companyIds = company::query()
            ->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower(trim((string) $client->company_name))])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($companyIds === []) {
            throw new \RuntimeException(__('No company found for the selected client.'));
        }

        $location->companies()->sync($companyIds);
    }

    private function validateClientSelection(Request $request): array
    {
        return $this->validateLocationHeadSelection($request);
    }

    private function validateLocationHeadSelection(Request $request): array
    {
        $soloCompanyId = $this->resolveSolochoicezCompanyId();

        if (! $soloCompanyId) {
            return [];
        }

        $errors = [];

        foreach ($this->normalizeLocationHeadIds($request) as $headId) {
            $headAllowed = Employee::withoutGlobalScope(AuthCompanyScope::class)
                ->where('id', $headId)
                ->where('company_id', $soloCompanyId)
                ->where('is_active', 1)
                ->whereNull('exit_date')
                ->exists();

            if (! $headAllowed) {
                $errors[] = __('Location head must be a Solochoicez employee.');
            }
        }

        return $errors;
    }

    private function normalizeLocationHeadIds(Request $request): array
    {
        $headIds = array_values(array_unique(array_filter(array_map(
            'intval',
            (array) $request->input('location_head_ids', [])
        ))));

        if ($headIds !== []) {
            return $headIds;
        }

        if ($request->filled('location_head')) {
            return [(int) $request->location_head];
        }

        return [];
    }

    private function validateLocationEmployeeSelection(Request $request): array
    {
        $companyIds = array_values(array_filter(array_map('intval', (array) $request->input('company_ids', []))));

        if (empty($companyIds)) {
            return [];
        }

        $errors = [];
        $allowedEmployeeQuery = Employee::query()
            ->whereIn('company_id', $companyIds)
            ->where('is_active', 1)
            ->whereNull('exit_date');

        foreach ($this->normalizeLocationHeadIds($request) as $headId) {
            $headAllowed = (clone $allowedEmployeeQuery)
                ->where('id', $headId)
                ->exists();

            if (! $headAllowed) {
                $errors[] = __('Location head must be an employee of the selected company.');
            }
        }

        $employeeIds = array_values(array_filter(array_map('intval', (array) $request->input('employee_ids', []))));

        if (! empty($employeeIds)) {
            $allowedCount = (clone $allowedEmployeeQuery)
                ->whereIn('id', $employeeIds)
                ->count();

            if ($allowedCount !== count($employeeIds)) {
                $errors[] = __('Selected employees must belong to the chosen companies.');
            }
        }

        return $errors;
    }

    private function validateLocationShiftSelection(Request $request): array
    {
        $companyIds = array_values(array_filter(array_map('intval', (array) $request->input('company_ids', []))));

        if (empty($companyIds)) {
            return [];
        }

        $errors = [];

        foreach ((array) $request->input('shifts', []) as $item) {
            $companyId = (int) ($item['company_id'] ?? 0);
            $shiftId = (int) ($item['office_shift_id'] ?? 0);

            if ($shiftId === 0) {
                continue;
            }

            if (! in_array($companyId, $companyIds, true)) {
                $errors[] = __('Office shift must belong to one of the selected companies.');

                continue;
            }

            $shift = office_shift::find($shiftId);

            if (! $shift || (int) $shift->company_id !== $companyId) {
                $errors[] = __('Office shift does not belong to the selected company.');
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param  array<int>|null  $allowedCompanyIds
     */
    private function applyOfficeShiftsToLocation(int $locationId, Request $request, ?array $allowedCompanyIds = null): int
    {
        $shifts = (array) $request->input('shifts', []);

        if (empty($shifts)) {
            return 0;
        }

        if ($allowedCompanyIds === null) {
            $location = location::with('companies:id')->findOrFail($locationId);
            $allowedCompanyIds = $location->companies->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $totalUpdated = 0;

        foreach ($shifts as $item) {
            $companyId = (int) ($item['company_id'] ?? 0);
            $shiftId = (int) ($item['office_shift_id'] ?? 0);

            if ($shiftId === 0) {
                continue;
            }

            if (! in_array($companyId, $allowedCompanyIds, true)) {
                throw new \RuntimeException(__('Selected company is not linked to this location.'));
            }

            $shift = office_shift::find($shiftId);

            if (! $shift || (int) $shift->company_id !== $companyId) {
                throw new \RuntimeException(__('Office shift does not belong to the selected company.'));
            }

            $totalUpdated += Employee::query()
                ->where('location_id', $locationId)
                ->where('company_id', $companyId)
                ->where('is_active', 1)
                ->whereNull('exit_date')
                ->update(['office_shift_id' => $shiftId]);
        }

        return $totalUpdated;
    }

    private function assignEmployeesToLocation(int $locationId, Request $request): void
    {
        $companyIds = (array) $request->input('company_ids', []);
        $scope = $request->input('assign_scope', 'specific');
        $employeeIds = (array) $request->input('employee_ids', []);

        if (empty($companyIds)) {
            return;
        }

        if ($scope === 'all') {
            Employee::whereIn('company_id', $companyIds)
                ->update(['location_id' => $locationId]);

            return;
        }

        if (! empty($employeeIds)) {
            Employee::whereIn('id', $employeeIds)
                ->whereIn('company_id', $companyIds)
                ->update(['location_id' => $locationId]);
        }
    }


	public function delete($id)
	{

		if(!env('USER_VERIFIED'))
		{
			return response()->json(['success' => 'This feature is disabled for demo!']);
		}
		$logged_user = auth()->user();

		if ($logged_user->can('delete-location'))
		{
		     location::whereId($id)->delete();
		     return "success";

		}
		return response()->json(['success' => __('You are not authorized')]);
	}


	public function delete_by_selection(Request $request)
	{
		if(!env('USER_VERIFIED'))
		{
			return response()->json(['error' => 'This feature is disabled for demo!']);
		}
		$logged_user = auth()->user();

		if ($logged_user->can('delete-location'))
		{

			$location_id = $request['locationIdArray'];
			$location = location::whereIntegerInRaw('id', $location_id);
			if ($location->delete())
			{
				return response()->json(['success' => __('Multi Delete',['key'=>trans('file.Location')])]);
			}
			else {
				return response()->json(['error' => 'Error selected Locations can not be deleted']);
			}
		}
		return response()->json(['success' => __('You are not authorized')]);
	}


}
