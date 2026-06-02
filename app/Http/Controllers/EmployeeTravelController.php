<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Travel;
use Illuminate\Http\Request;

class EmployeeTravelController extends Controller
{
    public function profileIndex(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['error' => __('Unauthorized')], 401);
        }

        $employeeId = (int) Employee::query()
            ->where('id', (int) $user->id)
            ->orWhere('email', (string) $user->email)
            ->value('id');

        $requestedEmployeeId = (int) $request->get('employee_id');
        if ($employeeId < 1 && $requestedEmployeeId > 0) {
            $employeeId = $requestedEmployeeId;
        }

        if ($employeeId < 1) {
            return datatables()->of(collect())->make(true);
        }

        return $this->index($employeeId);
    }

    public function index($employee)
    {
        $logged_user = auth()->user();
        $employeeId = (int) $employee;
        $currentEmployeeId = (int) Employee::query()
            ->where('id', (int) optional($logged_user)->id)
            ->orWhere('email', (string) optional($logged_user)->email)
            ->value('id');

        if (! $logged_user) {
            return response()->json(['error' => __('Unauthorized')], 401);
        }

        if (! $logged_user->can('view-details-employee') && $currentEmployeeId !== $employeeId) {
            return datatables()->of(collect())->make(true);
        }

        $travels = Travel::query()
            ->with('company', 'employee', 'TravelType')
            ->where('employee_id', $employeeId)
            ->orderByDesc('id')
            ->get();

        return datatables()->of($travels)
            ->setRowId(function ($travel) {
                return $travel->id;
            })
            ->addColumn('summary', function ($data) {
                $expected = $data->expected_budget ?? '0';
                $actual = $data->actual_budget ?? '0';
                $status = e($data->status ?? '');
                $purpose = e($data->purpose_of_visit ?? '');

                return $purpose
                    . "<br><br><b><i>".__('Expected Budget :')."</i></b> {$expected}"
                    . "<br><b><i>".__('Actual Budget :')."</i></b> {$actual}"
                    . "<br><div class='badge badge-success'>{$status}</div><br>";
            })
            ->addColumn('action', function ($data) use ($employeeId, $logged_user, $currentEmployeeId) {
                if (! $logged_user->can('view-details-employee') && $currentEmployeeId !== $employeeId) {
                    return '';
                }

                return '<button type="button" name="show_travel" id="'.$data->id.'" class="show_travel btn btn-success btn-sm"><i class="dripicons-preview"></i></button>';
            })
            ->rawColumns(['summary', 'action'])
            ->make(true);
    }

    public function show($id)
    {
        if (! request()->ajax()) {
            return abort(404);
        }

        $data = Travel::findOrFail($id);
        $logged_user = auth()->user();
        $currentEmployeeId = (int) Employee::query()
            ->where('id', (int) optional($logged_user)->id)
            ->orWhere('email', (string) optional($logged_user)->email)
            ->value('id');

        if (! $logged_user->can('view-details-employee') && $currentEmployeeId !== (int) $data->employee_id) {
            return response()->json(['error' => __('You are not authorized')], 403);
        }

        $company_name = $data->company->company_name ?? '';
        $employee_name = $data->employee->full_name ?? '';
        $arrangement_name = $data->TravelType->arrangement_type ?? '';

        return response()->json([
            'data' => $data,
            'employee_name' => $employee_name,
            'company_name' => $company_name,
            'arrangement_name' => $arrangement_name,
        ]);
    }
}
