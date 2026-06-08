<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\company;
use App\Models\location;
use App\Support\CompanyScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use Spatie\Permission\Models\Role;


class LocationController extends Controller
{
	public function index()
	{
		$countries = \DB::table('countries')->select('id','name')->get();
		$employeesQuery = Employee::select('id','first_name','last_name')->where('is_active',1)->where('exit_date',NULL);
        if (CompanyScope::applies() && ($scopedCompanyId = CompanyScope::companyId())) {
            $employeesQuery->where('company_id', $scopedCompanyId);
        }
        $employees = $employeesQuery->get();
        $companies = CompanyScope::companiesForSelect();

		if(request()->ajax())
		{
			return datatables()->of(location::with('Country:id,name','LocationHead:id,first_name,last_name', 'companies:id,company_name')->latest()->get())
				->addColumn('country', function ($row)
				{
					return optional($row->Country)->name ?? '';
				})
				->addColumn('location_head', function ($row)
				{
					return $row->LocationHead->full_name ?? ' ' ;
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
					if (auth()->user()->can('edit-location'))
					{
						$button .= '<button type="button" name="delete" id="' . $data->id . '" class="delete btn btn-danger btn-sm"><i class="dripicons-trash"></i></button>';
					}
					return $button;
				})
				->rawColumns(['action'])
				->make(true);
		}
		return view('organization.location.index',compact('countries','employees', 'companies'));
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
                    'assign_scope' => 'nullable|in:specific,all'
				]
			);


			if ($validator->fails())
			{
				return response()->json(['errors' => $validator->errors()->all()]);
			}


			$data = [];

			$data['location_name'] = $request->location_name;
			if ($request->location_head)
			{
				$data['location_head'] = $request->location_head;
			}
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
                $this->assignEmployeesToLocation($location->id, $request);
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
			$data = location::findOrFail($id);
			return response()->json([
                'data' => $data,
                'company_ids' => $data->companies()->pluck('companies.id')->toArray(),
            ]);
		}
	}






	public function update(Request $request)
	{

		$logged_user = auth()->user();

		if ($logged_user->can('edit-location'))
		{
           $id = $request->hidden_id;

			$data = $request->only('location_name', 'location_head', 'address1', 'address2', 'city',
				'state', 'country', 'zip', 'latitude', 'longitude', 'max_radius');

			if ($data['location_head'] == '')
			{
				$data['location_head'] = null;
			}
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
                    'assign_scope' => 'nullable|in:specific,all'
				]
			);



			if ($validator->fails())
			{
				return response()->json(['errors'=>$validator->errors()->all()]);
			}


            DB::beginTransaction();
            try {
                location::whereId($id)->update($data);
                $location = location::findOrFail($id);
                $location->companies()->sync($request->company_ids);
                $this->assignEmployeesToLocation($id, $request);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json(['errors' => [$e->getMessage()]]);
            }

			return response()->json(['success' => __('Data is successfully updated')]);

		}
		return response()->json(['success' => __('You are not authorized')]);
	}

    public function employeesByCompanies(Request $request)
    {
        $companyIds = $request->input('company_ids', []);
        if (is_string($companyIds)) {
            $companyIds = array_filter(array_map('intval', explode(',', $companyIds)));
        }

        $employees = Employee::query()
            ->select('id', 'first_name', 'last_name', 'company_id')
            ->where('is_active', 1)
            ->whereNull('exit_date')
            ->when(! empty($companyIds), function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            })
            ->orderBy('first_name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'full_name' => $item->full_name,
                    'company_id' => $item->company_id,
                ];
            });

        return response()->json(['employees' => $employees]);
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
