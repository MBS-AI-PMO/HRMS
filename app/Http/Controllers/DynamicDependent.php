<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\company;
use App\Models\department;
use App\Models\designation;
use App\Models\Employee;
use App\Models\location;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Support\ClientDisplay;
use App\Support\CompanyScope;
use App\Scopes\AuthCompanyScope;
use App\Models\FinanceBankCash;
use App\Models\JobCandidate;
use App\Models\office_shift;
use App\Models\SupportTicket;
use App\Models\TaxType;
use App\Support\ManagedEmployeeScope;
use Illuminate\Http\Request;

class DynamicDependent extends Controller {

	public function fetchDepartment(Request $request)
	{
		$value = null;

		if ($request->filled('client_id')) {
			$value = CompanyScope::resolveCompanyIdForClient((int) $request->client_id);

			if (! $value && $request->filled('value')) {
				$value = (int) $request->get('value');
			}
		} elseif ($request->filled('value')) {
			$value = (int) $request->get('value');

			if (CompanyScope::applies()) {
				$scopedId = CompanyScope::companyId();

				if ($scopedId) {
					$value = $scopedId;
				} elseif (! $value) {
					return '';
				}
			}
		}

		if (! $value) {
			return '';
		}

		$data = department::withoutGlobalScope(AuthCompanyScope::class)
			->where('company_id', $value)
			->orderBy('department_name')
			->get()
			->unique('department_name')
			->values();

		$output = '';
		foreach ($data as $row) {
			$output .= '<option value="' . $row->id . '">' . e($row->department_name) . '</option>';
		}

		return $output;
	}

	public function fetchOfficeShifts(Request $request)
	{
		$dependent = $request->get('dependent', 'shift_name');

		if ($request->filled('client_id')) {
			$data = office_shift::query()
				->where('client_id', (int) $request->client_id)
				->orderBy('shift_name')
				->get();
		} else {
			$value = CompanyScope::resolveCompanyIdForInput((int) $request->get('value'));
			$data = office_shift::query()
				->where('company_id', $value)
				->whereNull('client_id')
				->orderBy('shift_name')
				->get();
		}

		$output = '';
		foreach ($data as $row)
		{
			$output .= '<option value=' . $row->id . '>' . $row->$dependent . '</option>';
		}

		return $output;
	}

	public function fetchEmployee(Request $request)
	{
		$loggedUser = auth()->user();
		$useManagedScope = $loggedUser
			&& ManagedEmployeeScope::canAccessScopedEmployeeList((int) $loggedUser->id, (int) $loggedUser->role_users_id);
		$value = $useManagedScope
			? (int) $request->get('value')
			: CompanyScope::resolveCompanyIdForInput((int) $request->get('value'));
		$first_name = $request->get('first_name');
		$last_name = $request->get('last_name');

		$dataQuery = Employee::withoutGlobalScope(\App\Scopes\AuthCompanyScope::class)
			->where('is_active', 1)
			->where(function ($query) {
				$query->whereNull('exit_date')
					->orWhere('exit_date', '>=', date('Y-m-d'))
					->orWhere('exit_date', '0000-00-00');
			});

		if ($request->filled('client_id')) {
			$dataQuery->where('client_id', (int) $request->client_id);
		} elseif ($value) {
			$linkedClientIds = CompanyScope::clientIdsForCompany($value);

			$dataQuery->where(function ($query) use ($value, $linkedClientIds) {
				$query->where('company_id', $value);

				if ($linkedClientIds !== []) {
					$query->orWhereIn('client_id', $linkedClientIds);
				}
			});
		} else {
			return '';
		}

		if ($useManagedScope) {
			$managedEmployeeIds = ManagedEmployeeScope::managedEmployeeIds((int) $loggedUser->id);
			$dataQuery->whereIn('id', $managedEmployeeIds);
		}

		if ($request->filled('location_id')) {
			$dataQuery->where('location_id', (int) $request->location_id);
		}

		$data = $dataQuery->get();

		$output = '';
		foreach ($data as $row)
		{
			$output .= '<option value=' . $row->id . '>' . $row->$first_name . ' ' . $row->$last_name . '</option>';
		}

		return $output;
	}

	public function fetchProjectEmployees(Request $request)
	{
		$clientId = (int) $request->get('value');

		if (! $clientId) {
			return '';
		}

		$client = Client::query()->find($clientId);

		if (! $client) {
			return '';
		}

		$companyId = CompanyScope::resolveCompanyIdForClient($clientId);

		if (! $companyId) {
			$companyName = trim((string) $client->company_name);
			$companyId = $companyName !== ''
				? company::query()
					->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower($companyName)])
					->value('id')
				: null;
		}

		if (! $companyId) {
			$companyId = CompanyScope::companyId();
		}

		$first_name = $request->get('first_name', 'first_name');
		$last_name = $request->get('last_name', 'last_name');
		$loggedUser = auth()->user();
		$useManagedScope = $loggedUser
			&& ManagedEmployeeScope::canAccessScopedEmployeeList((int) $loggedUser->id, (int) $loggedUser->role_users_id);

		$dataQuery = Employee::withoutGlobalScope(AuthCompanyScope::class)
			->where('is_active', 1)
			->where(function ($query) {
				$query->whereNull('exit_date')
					->orWhere('exit_date', '>=', date('Y-m-d'))
					->orWhere('exit_date', '0000-00-00');
			})
			->where(function ($query) use ($clientId, $companyId) {
				$query->where('client_id', $clientId);

				if ($companyId) {
					$query->orWhere('company_id', (int) $companyId);
				}
			})
			->orderBy('first_name')
			->orderBy('last_name');

		if ($useManagedScope) {
			$dataQuery->whereIn('id', ManagedEmployeeScope::managedEmployeeIds((int) $loggedUser->id));
		}

		$output = '';

		foreach ($dataQuery->get() as $row) {
			$output .= '<option value="'.$row->id.'">'.e($row->$first_name.' '.$row->$last_name).'</option>';
		}

		return $output;
	}

	public function fetchProjectCategories(Request $request)
	{
		$includeId = (int) $request->get('include_id');

		$categories = ProjectCategory::query()
			->where(function ($query) use ($includeId) {
				$query->where('is_active', true);

				if ($includeId) {
					$query->orWhere('id', $includeId);
				}
			})
			->orderBy('category_name')
			->get(['id', 'category_name']);

		$output = '';

		foreach ($categories as $row) {
			$output .= '<option value="'.$row->id.'">'.e($row->category_name).'</option>';
		}

		return $output;
	}

	public function fetchEmployeeDepartment(Request $request)
	{
		$value = $request->get('value');
		$first_name = $request->get('first_name');
		$last_name = $request->get('last_name');
		$loggedUser = auth()->user();
		$useManagedScope = $loggedUser
			&& ManagedEmployeeScope::canAccessScopedEmployeeList((int) $loggedUser->id, (int) $loggedUser->role_users_id);

		$data = Employee::wheredepartment_id($value)
                    ->where('is_active',1)
                    ->where(function ($query) {
						$query->whereNull('exit_date')
							->orWhere('exit_date', '>=', date('Y-m-d'))
							->orWhere('exit_date', '0000-00-00');
					});
		if ($useManagedScope) {
			$data->whereIn('id', ManagedEmployeeScope::managedEmployeeIds((int) $loggedUser->id));
		}
		$data = $data->get();
		$output = '';
		foreach ($data as $row)
		{
			$output .= '<option value=' . $row->id . '>' . $row->$first_name . ' ' . $row->$last_name . '</option>';
		}

		return $output;
	}

	public function fetchClients(Request $request)
	{
		$companyId = CompanyScope::resolveCompanyIdForInput((int) $request->get('value'));

		if (! $companyId) {
			return '';
		}

		$clientIds = CompanyScope::clientIdsForCompany($companyId);

		if ($clientIds === []) {
			return '';
		}

		$clients = Client::query()
			->select('id', 'company_name', 'first_name', 'last_name')
			->whereIn('id', $clientIds)
			->orderBy('first_name')
			->orderBy('last_name')
			->orderBy('company_name')
			->get();

		$output = '';

		foreach ($clients as $client) {
			$output .= '<option value="'.$client->id.'">'.e(ClientDisplay::label($client)).'</option>';
		}

		return $output;
	}

	public function fetchLocations(Request $request)
	{
		$companyId = $request->filled('company_id')
			? CompanyScope::resolveCompanyIdForInput((int) $request->company_id)
			: null;
		$clientId = $request->filled('client_id') ? (int) $request->client_id : null;

		$query = location::query()
			->select('id', 'location_name', 'client_id')
			->orderBy('location_name');

		if (CompanyScope::applies()) {
			$scopedCompanyId = CompanyScope::companyId();

			if (! $scopedCompanyId) {
				return '';
			}

			$query->whereHas('companies', function ($companyQuery) use ($scopedCompanyId) {
				$companyQuery->where('companies.id', $scopedCompanyId);
			});
		}

		if ($companyId) {
			$companyClientIds = collect(CompanyScope::clientIdsForCompany($companyId));

			$query->where(function ($builder) use ($companyId, $clientId, $companyClientIds) {
				$builder->whereHas('companies', function ($companyQuery) use ($companyId) {
					$companyQuery->where('companies.id', $companyId);
				});

				if ($clientId) {
					$builder->orWhere('client_id', $clientId);
				} elseif ($companyClientIds->isNotEmpty()) {
					$builder->orWhereIn('client_id', $companyClientIds);
				}
			});
		}

		if ($clientId) {
			$query->where('client_id', $clientId);
		}

		$output = '';

		foreach ($query->get() as $location) {
			$output .= '<option value="'.$location->id.'">'.e($location->location_name).'</option>';
		}

		return $output;
	}

	public function fetchDesignationDepartment(Request $request)
	{
		$value = $request->get('value');
		$designation_name = $request->get('designation_name');
		$data = designation::withoutGlobalScope(AuthCompanyScope::class)
			->where('department_id', $value)
			->orderBy('designation_name')
			->get()
			->unique('designation_name')
			->values();
		$output = '';

		foreach ($data as $row)
		{
			$output .= '<option value="' . $row->id . '">' . e($row->$designation_name) . '</option>';
		}

		return $output;
	}

	public function fetchBalance(Request $request)
	{
		$value = $request->get('value');
		$dependent = $request->get('dependent');
		$data = FinanceBankCash::whereId($value)->pluck('account_balance')->first();
		$output = '';
		$output .= '<p> (Available Balance ' . $data  .  ' )</p>';
		return $output;
	}

	public function companyEmployee(SupportTicket $ticket){
		$value = $ticket->company_id;
		$data = Employee::whereCompany_id($value)
                ->where('is_active',1)
                ->where('exit_date',NULL)
                ->get();
		$output = '';
		foreach ($data as $row)
		{
			$output .= '<option value=' . $row->id . '>' . $row->first_name . ' ' . $row->last_name . '</option>';
		}

		return $output;
	}


	public function getTaxRate(Request $request)
	{
		$value = $request->get('value');
		$qty = $request->get('qty');
		$unit_price = $request->get('unit_price');

		$data = TaxType::findorFail($value);
		$total_cost = $qty * $unit_price;
		if($data->type=='fixed')
		{
			$tax = $data->rate;
			$sub_total = $total_cost + $tax;
		}
		else {
			$tax = (($total_cost)*($data->rate/100));
			$sub_total = $total_cost + $tax;
		}

		return response()->json(['data'=>$data,'sub_total'=>$sub_total,'tax'=>$tax,'total_cost'=>$total_cost]);

	}


	public function fetchCandidate(Request $request)
	{
		$value = $request->get('value');

		$data = JobCandidate::whereJob_id($value)->groupBy('full_name')->get();
		$output = '';
		foreach ($data as $row)
		{
			$output .= '<option value=' . $row->id . '>' . $row->full_name . '</option>';
		}

		return $output;
	}

}
