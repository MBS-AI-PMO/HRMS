<?php

namespace App\Http\Controllers;

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

        return $this->index((int) $user->id);
    }

    public function index($employee)
    {
        $logged_user = auth()->user();
        $employeeId = (int) $employee;

        if (! $logged_user) {
            return response()->json(['error' => __('Unauthorized')], 401);
        }

        if (! $logged_user->can('view-details-employee') && (int) $logged_user->id !== $employeeId) {
            return datatables()->of(collect())->make(true);
        }

        if (request()->ajax()) {
            return datatables()->of(Travel::query()->where('employee_id', $employeeId))
                ->setRowId(function ($travel) {
                    return $travel->id;
                })
                ->addColumn('action', function ($data) use ($employeeId, $logged_user) {
                    if (! $logged_user->can('view-details-employee') && (int) $logged_user->id !== $employeeId) {
                        return '';
                    }

                    return '<button type="button" name="show_travel" id="'.$data->id.'" class="show_travel btn btn-success btn-sm"><i class="dripicons-preview"></i></button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return datatables()->of(collect())->make(true);
    }

    public function show($id)
    {
        if (! request()->ajax()) {
            return abort(404);
        }

        $data = Travel::findOrFail($id);
        $logged_user = auth()->user();

        if (! $logged_user->can('view-details-employee') && (int) $logged_user->id !== (int) $data->employee_id) {
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
