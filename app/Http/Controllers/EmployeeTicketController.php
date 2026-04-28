<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;

class EmployeeTicketController extends Controller {

	public function index($employee)
	{
		$logged_user = auth()->user();

		if ($logged_user->can('view-details-employee') || $logged_user->id == $employee)
		{
			if (request()->ajax())
			{
				return datatables()->of(SupportTicket::where('employee_id', $employee)->get())
					->setRowId(function ($ticket)
					{
						return $ticket->id;
					})
					->addColumn('ticket_details', function ($row)
					{
						if ($row->ticket_attachment)
						{
							return $row->ticket_code . '<br><h6><a href="' . route('tickets.downloadTicket', $row->id) . '">' . trans('file.File') . '</a></h6>';
//'<br><td><b><i>"' . $row->ticket_status.'"</i></b>';
						} else
						{
							return $row->ticket_code;
						}
					})
					->addColumn('action', function ($data) use ($logged_user,$employee)
					{
						$button = '';
						// if (auth()->user()->can('view-details-employee') || $logged_user->id == $employee)
						if (auth()->user()->can('view-details-employee') || auth()->user()->can('view-ticket'))						{
							$button = '<a id="' . $data->id . '" class="show btn btn-primary btn-sm" href="' . route('tickets.show', $data) . '"><i class="dripicons-preview"></i></a>';
						}

						return $button;
					})
					->rawColumns(['action', 'ticket_details'])
					->make(true);
			}
		}
	}

    public function myComplains()
    {
        $logged_user = auth()->user();
        $employeeId = (int) $logged_user->id;

        if (request()->ajax())
        {
            $assignedTicketIds = DB::table('employee_support_ticket')
                ->where('employee_id', $employeeId)
                ->pluck('support_ticket_id');

            $myTickets = SupportTicket::query()
                ->where('employee_id', $employeeId)
                ->orWhereIn('id', $assignedTicketIds)
                ->get();

            return datatables()->of($myTickets)
                ->setRowId(function ($ticket)
                {
                    return $ticket->id;
                })
                ->addColumn('ticket_details', function ($row)
                {
                    if ($row->ticket_attachment)
                    {
                        return $row->ticket_code . '<br><h6><a href="' . route('tickets.downloadTicket', $row->id) . '">' . trans('file.File') . '</a></h6>';
                    } else
                    {
                        return $row->ticket_code;
                    }
                })
                ->addColumn('action', function ($data) use ($employeeId)
                {
                    $button = '';
                    if (auth()->user()->can('view-ticket') || (int) $data->employee_id === $employeeId) {
                        $button = '<a id="' . $data->id . '" class="show btn btn-primary btn-sm" href="' . route('tickets.show', $data) . '"><i class="dripicons-preview"></i></a>';
                    }

                    return $button;
                })
                ->rawColumns(['action', 'ticket_details'])
                ->make(true);
        }

        return response()->json(['success' => __('You are not authorized')]);
    }
}
