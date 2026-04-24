<?php

namespace App\Http\Controllers;

use App\Models\leave;

class EmployeeLeaveController extends Controller {

	//

	public function index($employee)
	{
		$logged_user = auth()->user();

		if ($logged_user->can('view-details-employee') || (int) $logged_user->id === (int) $employee)
		{
			if (request()->ajax())
			{
				$isWfhView = request()->boolean('wfh');
				$leaves = leave::with('department', 'LeaveType')
					->where('employee_id', $employee)
					->when($isWfhView, function ($query) {
						$query->whereHas('LeaveType', function ($q) {
							$q->where('leave_type', 'like', '%wfh%')
								->orWhere('leave_type', 'like', '%work from home%');
						});
					}, function ($query) {
						$query->where(function ($subQuery) {
							$subQuery->whereDoesntHave('LeaveType', function ($q) {
								$q->where('leave_type', 'like', '%wfh%')
									->orWhere('leave_type', 'like', '%work from home%');
							})
							->orWhereNull('leave_type_id');
						});
					})
					->get();

				return datatables()->of($leaves)
					->setRowId(function ($leave)
					{
						return $leave->id;
					})
					->addColumn('leave_type', function ($row)
					{
						return empty($row->LeaveType->leave_type) ? '' : $row->LeaveType->leave_type;
					})
					->addColumn('department', function ($row)
					{
						return empty($row->department->department_name) ? '' : $row->department->department_name;
					})
					->addColumn('action', function ($data) use ($employee, $logged_user, $isWfhView)
					{
						$button = '';
						if (auth()->user()->can('view-details-employee') || $logged_user->id == $employee)
						{
							$btnClass = $isWfhView ? 'show_wfh_leave' : 'show_leave';
							$button = '<button type="button" name="show_leave" id="' . $data->id . '" class="' . $btnClass . ' btn btn-success btn-sm"><i class="dripicons-preview"></i></button>';
						}

						return $button;
					})
					->rawColumns(['action'])
					->make(true);
			}
		}
	}

	public function show($id)
	{
		if (request()->ajax())
		{
			$data = leave::findOrFail($id);
			$company_name = $data->company->company_name ?? '';
			$department = $data->department->department_name ?? '';
			$leave_type_name = $data->LeaveType->leave_type ?? '';
			$employee_name = $data->employee->full_name;
			$start_date_name = $data->start_date;
			$end_date_name = $data->end_date;

			return response()->json(['data' => $data, 'company_name' => $company_name, 'employee_name' => $employee_name, 'department' => $department, 'leave_type_name' => $leave_type_name,
				'start_date_name' => $start_date_name, 'end_date_name' => $end_date_name]);
		}
	}

}
