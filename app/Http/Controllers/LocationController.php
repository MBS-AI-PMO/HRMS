<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\company;
use App\Models\location;
use App\Models\office_shift;
use App\Scopes\AuthCompanyLocationScope;
use App\Scopes\AuthCompanyScope;
use App\Support\CompanyScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use Spatie\Permission\Models\Role;


class LocationController extends Controller
{
    protected function canEditLocation(int $locationId): bool
    {
        $user = auth()->user();

        if ($user->can('edit-location')) {
            return true;
        }

        return location::userHeadsLocation((int) $user->id, $locationId);
    }

    protected function canAssignShiftAtLocation(int $locationId): bool
    {
        $user = auth()->user();

        if ($user->can('view-location')) {
            return true;
        }

        return location::userHeadsLocation((int) $user->id, $locationId);
    }

    protected function isHeadOnlyLocationManager(): bool
    {
        $user = auth()->user();

        return location::userIsLocationHead((int) $user->id) && ! $user->can('edit-location');
    }

	public function index()
	{
		$countries = \DB::table('countries')->select('id','name')->get();
        $companies = CompanyScope::companiesForSelect();

		if(request()->ajax())
		{
			return datatables()->of(location::with('Country:id,name', 'locationHeads:id,first_name,last_name', 'companies:id,company_name')->latest()->get())
				->addColumn('country', function ($row)
				{
					return optional($row->Country)->name ?? '';
				})
				->addColumn('location_head', function ($row)
				{
					return $row->locationHeadsLabel();
				})
                ->addColumn('companies', function ($row) {
                    return $row->companies->pluck('company_name')->implode(', ');
                })
				->addColumn('action', function($data){
					$button = '';
					if (auth()->user()->can('edit-location'))
					{
						$button = '<button type="button" name="edit" id="' . $data->id . '" class="edit btn btn-primary btn-sm"><i class="dripicons-pencil"></i></button>';
						$button .= '&nbsp;&nbsp;';
					}
					if (auth()->user()->can('view-location'))
					{
						$button .= '<button type="button" name="assign_shift" id="' . $data->id . '" class="assign_shift btn btn-info btn-sm" title="'.__('Assign Shift').'"><i class="fa fa-clock-o"></i></button>';
						$button .= '&nbsp;&nbsp;';
					}
					if (auth()->user()->can('delete-location'))
					{
						$button .= '<button type="button" name="delete" id="' . $data->id . '" class="delete btn btn-danger btn-sm"><i class="dripicons-trash"></i></button>';
					}
					return $button;
				})
				->rawColumns(['action'])
				->make(true);
		}
		return view('organization.location.index', compact('countries', 'companies'));
	}


	public function store(Request $request)
	{

		$logged_user = auth()->user();

		if ($logged_user->can('store-location'))
		{

			$validator = Validator::make($request->only('location_name', 'location_head', 'address1', 'address2', 'city',
				'state', 'country', 'zip', 'latitude', 'longitude', 'max_radius', 'company_ids'),
				[
					'location_name' => 'required|unique:locations,location_name,',
					'address1' => 'required',
					'zip' => 'nullable|numeric',
					'country'=> 'required',
                    'latitude' => 'nullable|numeric|between:-90,90',
                    'longitude' => 'nullable|numeric|between:-180,180',
                    'max_radius' => 'nullable|numeric|min:0',
                    'company_ids' => 'required|array|min:1',
                    'company_ids.*' => 'exists:companies,id',
                    'employee_ids' => 'nullable|array',
                    'employee_ids.*' => 'exists:employees,id',
                    'assign_scope' => 'nullable|in:specific,all',
                    'shifts' => 'nullable|array',
                    'shifts.*.company_id' => 'required_with:shifts|exists:companies,id',
                    'shifts.*.office_shift_id' => 'nullable|exists:office_shifts,id',
                    'location_head_ids' => 'nullable|array',
                    'location_head_ids.*' => 'exists:employees,id',
				]
			);


			if ($validator->fails())
			{
				return response()->json(['errors' => $validator->errors()->all()]);
			}

            $employeeErrors = $this->validateLocationEmployeeSelection($request);

            if (! empty($employeeErrors)) {
                return response()->json(['errors' => $employeeErrors]);
            }

            $shiftErrors = $this->validateLocationShiftSelection($request);

            if (! empty($shiftErrors)) {
                return response()->json(['errors' => $shiftErrors]);
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
                $location->companies()->sync($request->company_ids);
                $location->locationHeads()->sync($locationHeadIds);
                $this->assignEmployeesToLocation($location->id, $request);
                $this->applyOfficeShiftsToLocation($location->id, $request);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json(['errors' => [$e->getMessage()]]);
            }

			return response()->json(['success' => __('Data Added successfully.')]);
		}
		return response()->json(['success' => __('You are not authorized')]);
	}


	public function edit($id)
	{

		if(request()->ajax())
		{
            if (! $this->canEditLocation((int) $id)) {
                return response()->json(['errors' => [__('You are not authorized')]], 403);
            }

			$data = location::withoutGlobalScope(AuthCompanyLocationScope::class)->findOrFail($id);
			return response()->json([
                'data' => $data,
                'company_ids' => $data->companies()->pluck('companies.id')->toArray(),
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

        if ($this->isHeadOnlyLocationManager()) {
            $request->merge([
                'company_ids' => $location->companies()->pluck('companies.id')->toArray(),
                'location_head_ids' => $location->locationHeads()->pluck('employees.id')->toArray(),
            ]);
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
				'state', 'country', 'zip', 'latitude', 'longitude', 'max_radius', 'company_ids'),
				[
					'location_name' => 'required|unique:locations,location_name,' . $id,
					'location_head' => 'nullable',
					'address1' => 'required',
					'zip' => 'nullable|numeric',
					'country'=> 'required',
                    'latitude' => 'nullable|numeric|between:-90,90',
                    'longitude' => 'nullable|numeric|between:-180,180',
                    'max_radius' => 'nullable|numeric|min:0',
                    'company_ids' => 'required|array|min:1',
                    'company_ids.*' => 'exists:companies,id',
                    'employee_ids' => 'nullable|array',
                    'employee_ids.*' => 'exists:employees,id',
                    'assign_scope' => 'nullable|in:specific,all',
                    'shifts' => 'nullable|array',
                    'shifts.*.company_id' => 'required_with:shifts|exists:companies,id',
                    'shifts.*.office_shift_id' => 'nullable|exists:office_shifts,id',
                    'location_head_ids' => 'nullable|array',
                    'location_head_ids.*' => 'exists:employees,id',
				]
			);



			if ($validator->fails())
			{
				return response()->json(['errors'=>$validator->errors()->all()]);
			}

            $employeeErrors = $this->validateLocationEmployeeSelection($request);

            if (! empty($employeeErrors)) {
                return response()->json(['errors' => $employeeErrors]);
            }

            $shiftErrors = $this->validateLocationShiftSelection($request);

            if (! empty($shiftErrors)) {
                return response()->json(['errors' => $shiftErrors]);
            }

            DB::beginTransaction();
            try {
                location::withoutGlobalScope(AuthCompanyLocationScope::class)->whereId($id)->update($data);
                $location = location::withoutGlobalScope(AuthCompanyLocationScope::class)->findOrFail($id);
                $location->companies()->sync($request->company_ids);
                $location->locationHeads()->sync($locationHeadIds);
                $this->assignEmployeesToLocation($id, $request);
                $this->applyOfficeShiftsToLocation($id, $request);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json(['errors' => [$e->getMessage()]]);
            }

			return response()->json(['success' => __('Data is successfully updated')]);
	}

    public function employeesByCompanies(Request $request)
    {
        if (! auth()->user()->can('view-location') && ! location::userIsLocationHead((int) auth()->id())) {
            return response()->json(['employees' => [], 'companies' => []], 403);
        }

        $companyIds = $request->input('company_ids', []);
        if (is_string($companyIds)) {
            $companyIds = array_filter(array_map('intval', explode(',', $companyIds)));
        }

        if (empty($companyIds)) {
            return response()->json(['employees' => []]);
        }

        if ($this->isHeadOnlyLocationManager()) {
            $allowedCompanyIds = CompanyScope::companiesForLocationHead((int) auth()->id())
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

    public function myLocations()
    {
        $userId = (int) auth()->id();

        if (! location::userIsLocationHead($userId) && ! auth()->user()->can('view-location')) {
            return abort(403, __('You are not assigned as a location head.'));
        }

        if (request()->ajax()) {
            $locations = location::withoutGlobalScope(AuthCompanyLocationScope::class)
                ->with([
                'companies:id,company_name',
                'locationHeads:id,first_name,last_name',
            ])->withCount([
                'employees as active_employees_count' => function ($query) {
                    $query->withoutGlobalScope(AuthCompanyScope::class)
                        ->where('is_active', 1)
                        ->whereNull('exit_date');
                },
            ]);

            if (! auth()->user()->can('view-location')) {
                $locations->headedByUser($userId);
            }

            return datatables()->of($locations->latest('id'))
                ->addColumn('companies', function ($row) {
                    return $row->companies->pluck('company_name')->implode(', ');
                })
                ->addColumn('location_heads', function ($row) {
                    return $row->locationHeadsLabel();
                })
                ->addColumn('action', function ($row) use ($userId) {
                    $canEdit = auth()->user()->can('edit-location')
                        || location::userHeadsLocation($userId, (int) $row->id);
                    $canAssignShift = auth()->user()->can('view-location')
                        || location::userHeadsLocation($userId, (int) $row->id);

                    $button = '<a href="'.route('employees.index', ['location_id' => $row->id]).'" class="btn btn-primary btn-sm">'
                        .__('View Employees').'</a>';

                    if ($canEdit) {
                        $button .= '&nbsp;&nbsp;<button type="button" name="edit" id="'.$row->id.'" class="edit btn btn-info btn-sm" title="'.__('Edit').'"><i class="dripicons-pencil"></i></button>';
                    }

                    if ($canAssignShift) {
                        $button .= '&nbsp;&nbsp;<button type="button" name="assign_shift" id="'.$row->id.'" class="assign_shift btn btn-warning btn-sm" title="'.__('Assign Shift').'"><i class="fa fa-clock-o"></i></button>';
                    }

                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $countries = \DB::table('countries')->select('id', 'name')->get();
        $companies = auth()->user()->can('view-location')
            ? CompanyScope::companiesForSelect()
            : CompanyScope::companiesForLocationHead($userId);
        $locationHeadManage = ! auth()->user()->can('edit-location');

        return view('organization.location.my', compact('countries', 'companies', 'locationHeadManage'));
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
