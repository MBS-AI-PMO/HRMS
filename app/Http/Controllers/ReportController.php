<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\company;
use App\Models\department;
use App\Models\Employee;
use App\Models\ExpenseType;
use App\Models\FinanceBankCash;
use App\Models\FinanceDeposit;
use App\Models\FinanceExpense;
use App\Models\FinanceTransaction;
use App\Models\Project;
use App\Models\Task;
use App\Models\Payslip;
use App\Models\TrainingList;
use App\Models\location;
use App\Support\ClientDisplay;
use App\Support\CompanyScope;
use App\Support\ManagedEmployeeScope;
use App\Support\NearestOfficeLocation;
use App\Support\ReverseGeocoder;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;


class ReportController extends Controller {

	public function payslip(Request $request)
	{
		$logged_user = auth()->user();
		$companies = company::all();
		$selected_date = empty($request->filter_month_year) ? now()->format('F-Y') : $request->filter_month_year ;

		if ($logged_user->can('report-payslip'))
		{
			if (request()->ajax())
			{
				if (!empty($request->filter_employee))
				{
					$payslips = DB::table('payslips')
						->join('employees', 'payslips.employee_id', '=', 'employees.id')
						->where('employees.id', $request->filter_employee)
						->where('payslips.month_year', $selected_date)
						->select('payslips.id', 'payslips.net_salary', 'payslips.month_year', 'payslips.payment_type', 'payslips.created_at',
							'employees.id', 'employees.first_name', 'employees.last_name'
						)
						->get();
				} elseif (!empty($request->filter_company))
				{
					$payslips = DB::table('payslips')
						->join('employees', 'payslips.employee_id', '=', 'employees.id')
						->where('employees.company_id', $request->filter_company)
						->where('payslips.month_year', $selected_date)
						->select('payslips.id', 'payslips.net_salary', 'payslips.month_year', 'payslips.payment_type', 'payslips.created_at',
							'employees.id', 'employees.first_name', 'employees.last_name'
						)
						->get();
				} else
				{
					$payslips = DB::table('payslips')
						->join('employees', 'payslips.employee_id', '=', 'employees.id')
						->where('payslips.month_year', $selected_date)
						->select('payslips.id', 'payslips.net_salary', 'payslips.month_year', 'payslips.payment_type', 'payslips.created_at',
							'employees.id', 'employees.first_name', 'employees.last_name'
						)
						->get();
				}


				return datatables()->of($payslips)
					->addColumn('employee_name', function ($row)
					{
						return $row->first_name . ' ' . $row->last_name;
					})
					->addColumn('created_at', function ($row)
					{
						return Carbon::parse($row->created_at)->format(env('Date_Format'));
					})
					->make(true);
			}

			return view('report.payslip_report', compact('companies'));
		}

		return abort('403', __('You are not authorized'));
	}


	public function attendance(Request $request)
	{
		$logged_user = auth()->user();
		$isLocationHead = location::userIsLocationHead((int) $logged_user->id);
		$useManagedScope = ManagedEmployeeScope::canAccessScopedEmployeeList((int) $logged_user->id, (int) $logged_user->role_users_id);
		$managedEmployeeIds = $useManagedScope
			? ManagedEmployeeScope::managedEmployeeIds((int) $logged_user->id)
			: [];

		$companies = $isLocationHead
			? CompanyScope::companiesForLocationHead((int) $logged_user->id)
			: CompanyScope::companiesForSelect();

		if ($companies->isEmpty()) {
			$companies = CompanyScope::companiesForSelect();
		}

		$start_date = Carbon::parse($request->filter_start_date)->format('Y-m-d') ?? '';
		$end_date = Carbon::parse($request->filter_end_date)->format('Y-m-d') ?? '';


		if ($logged_user->can('report-attendance'))
		{
			if (request()->ajax())
			{
				if ($request->employee_id)
				{
					if ($useManagedScope && ! in_array((int) $request->employee_id, $managedEmployeeIds, true)) {
						return response()->json(['error' => __('You are not authorized to view this employee attendance.')], 403);
					}

					$employee = Employee::with(['officeShift', 'employeeAttendance' => function ($query) use ($start_date, $end_date)
					{
						$query->whereBetween('attendance_date', [$start_date, $end_date]);
					},
						'employeeLeave' => function ($query) use ($start_date, $end_date)
						{
							$query->where('start_date', '>=', $start_date)
								->where('end_date', '<=', $end_date);
						},
						'company:id,company_name',
						'company.companyHolidays' => function ($query) use ($start_date, $end_date)
						{
							$query->where('start_date', '>=', $start_date)
								->where('end_date', '<=', $end_date);
						}
					])
						->select('id', 'company_id', 'first_name', 'last_name', 'office_shift_id')->findOrFail($request->employee_id);


					$all_attendances_array = $employee->employeeAttendance->groupBy('attendance_date')->toArray();


					$leaves = $employee->employeeLeave;

					$shift = $employee->officeShift;

					$holidays = $employee->company->companyHolidays;


					$begin = new DateTime($start_date);
					$end = new DateTime($end_date);
					$end->modify('+1 day');

					$interval = DateInterval::createFromDateString('1 day');
					$period = new DatePeriod($begin, $interval, $end);

					$date_range = [];
					foreach ($period as $dt)
					{
						$date_range[] = $dt->format(env('Date_Format'));
					}
				} else
				{
					$date_range = [];
					$employee = null;
					$all_attendances_array = null;
					$leaves = null;
					$holidays = null;
					$shift = null;
				}


				return datatables()->of($date_range)
					->setRowId(function ($row) use ($employee)
					{
						return $employee->id;
					})
					->addColumn('employee_name', function ($row) use ($employee)
					{
						return $employee->full_name;
					})
					->addColumn('company', function ($row) use ($employee)
					{
						return $employee->company->company_name;
					})
					->addColumn('attendance_date', function ($row)
					{
						return Carbon::parse($row)->format(env('Date_Format'));
					})
					->addColumn('attendance_status', function ($row) use ($all_attendances_array, $leaves, $holidays, $shift)
					{
						$day = strtolower(Carbon::parse($row)->format('l')) . '_in';

						if (is_null($shift->$day))
						{
							return __('Off Day');
						}

						if (array_key_exists($row, $all_attendances_array))
						{
							return trans('file.present');
						} else
						{
							foreach ($leaves as $leave)
							{
								if ($leave->start_date <= $row && $leave->end_date >= $row)
								{
									return __('On Leave');
								}
							}
							foreach ($holidays as $holiday)
							{
								if ($holiday->start_date <= $row && $holiday->end_date >= $row)
								{
									return __('On Holiday');
								}
							}

							return trans('Absent');
						}
					})
					->addColumn('clock_in', function ($row) use ($all_attendances_array)
					{
						if (array_key_exists($row, $all_attendances_array))
						{

							$first = current($all_attendances_array[$row])['clock_in'];

							return $first;
						} else
						{
							return '---';
						}
					})
					->addColumn('clock_out', function ($row) use ($all_attendances_array)
					{
						if (array_key_exists($row, $all_attendances_array))
						{

							$last = end($all_attendances_array[$row])['clock_out'];

							return $last;
						} else
						{
							return '---';
						}
					})
					->addColumn('total_work', function ($row) use ($all_attendances_array)
					{
						if (array_key_exists($row, $all_attendances_array))
						{

							$total = 0;
							foreach ($all_attendances_array[$row] as $all_attendance_item)
							{
								sscanf($all_attendance_item['total_work'], '%d:%d', $hour, $min);
								$total += $hour * 60 + $min;
							}
							if ($h = floor($total / 60))
							{
								$total %= 60;
							}

							return sprintf('%02d:%02d', $h, $total);
						} else
						{
							return '---';
						}
					})
					->make(true);
			}

			return view('report.attendance_report', compact('companies'));
		}

		return abort('403', __('You are not authorized'));
	}

	public function training(Request $request)
	{
		$logged_user = auth()->user();

		$companies = company::all('id', 'company_name');

		$start_date = Carbon::parse($request->filter_start_date)->format('Y-m-d') ?? '';
		$end_date = Carbon::parse($request->filter_end_date)->format('Y-m-d') ?? '';


		if ($logged_user->can('report-training'))
		{
			if (request()->ajax())
			{
				if ($request->company_id)
				{
					$trainings = TrainingList::with('company:id,company_name', 'trainer:id,first_name,last_name',
						'TrainingType:id,type', 'employees')
						->where('start_date', '>=', $start_date)
						->where('end_date', '<=', $end_date)
						->where('company_id', $request->company_id)->get();
				} else
				{
					$trainings = array();
				}

				return datatables()->of($trainings)
					->setRowId(function ($training)
					{
						return $training->id;
					})
					->addColumn('TrainingType', function ($row)
					{
						return empty($row->TrainingType->type) ? '' : $row->TrainingType->type;
					})
					->addColumn('company', function ($row)
					{
						return $row->company->company_name ?? ' ' ;
					})
					->addColumn('employee', function ($row)
					{
						$name = $row->employees->pluck('last_name', 'first_name');
						$collection = [];
						foreach ($name as $first => $last)
						{
							$full_name = $first . ' ' . $last;
							array_push($collection, $full_name);
						}

						return $collection;
					})
					->addColumn('trainer', function ($row)
					{
						return $row->trainer->first_name . ' ' . $row->trainer->last_name;
					})
					->addColumn('training_duration', function ($row)
					{
						return $row->start_date . ' ' . trans('file.To') . ' ' . $row->end_date;
					})
					->make(true);
			}

			return view('report.training_report', compact('companies'));
		}

		return abort('403', __('You are not authorized'));
	}

	public function project(Request $request)
	{

		$logged_user = auth()->user();
		$projects = Project::all('id', 'title');


		if ($logged_user->can('report-project'))
		{
			if (request()->ajax())
			{
				if (!empty($request->project_id && $request->project_status))
				{
					$projects = Project::with('assignedEmployees')
						->where('id', $request->project_id)
						->where('project_status', $request->project_status)
						->get();
				} elseif (!empty($request->project_id))
				{
					$projects = Project::with('assignedEmployees')
						->where('id', $request->project_id)
						->get();
				} elseif (!empty($request->project_status))
				{
					$projects = Project::with('assignedEmployees')
						->where('project_status', $request->project_status)
						->get();
				} else
				{
					$projects = Project::with('assignedEmployees')
						->get();
				}


				return datatables()->of($projects)
					->setRowId(function ($project)
					{
						return $project->id;
					})
					->addColumn('summary', function ($row)
					{
						$project = empty($row->title) ? '' : $row->title;

						return '<h6><a href="' . route('projects.show', $row) . '">' . $project . '</a></h6><br>';
					})
					->addColumn('assigned_employee', function ($row)
					{
						$assigned_name = $row->assignedEmployees()->pluck('last_name', 'first_name');
						$collection = [];
						foreach ($assigned_name as $first => $last)
						{
							$full_name = $first . ' ' . $last;
							array_push($collection, $full_name);
						}

						return $collection;
					})
					->rawColumns(['summary'])
					->make(true);
			}

			return view('report.project_report', compact('projects'));
		}

		return abort('403', __('You are not authorized'));
	}

	public function task(Request $request)
	{

		$logged_user = auth()->user();
		$tasks = Task::all('id', 'task_name');


		if ($logged_user->can('report-task'))
		{
			if (request()->ajax())
			{
				if (!empty($request->task_id && $request->task_status))
				{
					$tasks = Task::with('project:id,title', 'assignedEmployees', 'addedBy:id,username')
						->where('id', $request->task_id)
						->where('task_status', $request->task_status)
						->get();
				} elseif (!empty($request->task_id))
				{
					$tasks = Task::with('project:id,title', 'assignedEmployees', 'addedBy:id,username')
						->where('id', $request->task_id)
						->get();
				} elseif (!empty($request->task_status))
				{
					$tasks = Task::with('project:id,title', 'assignedEmployees', 'addedBy:id,username')
						->where('task_status', $request->task_status)
						->get();
				} else
				{
					$tasks = Task::with('project:id,title', 'assignedEmployees', 'addedBy:id,username')
						->get();
				}


				return datatables()->of($tasks)
					->setRowId(function ($task)
					{
						return $task->id;
					})
					->addColumn('task_name', function ($row)
					{
						$task_name = $row->task_name;
						$project = empty($row->project->title) ? '' : $row->project->title;

						return $task_name . '<br><h6><a href="' . route('projects.show', $row->project) . '">' . $project . '</a></h6>';
					})
					->addColumn('created_by', function ($row)
					{
						return $row->addedBy->username;
					})
					->addColumn('assigned_employee', function ($row)
					{
						$assigned_name = $row->assignedEmployees()->pluck('last_name', 'first_name');
						$collection = [];
						foreach ($assigned_name as $first => $last)
						{
							$full_name = $first . ' ' . $last;
							array_push($collection, $full_name);
						}

						return $collection;
					})
					->rawColumns(['task_name'])
					->make(true);
			}

			return view('report.task_report', compact('tasks'));
		}

		return abort('403', __('You are not authorized'));
	}

	public function employees(Request $request)
	{

		$logged_user = auth()->user();
		$companies = company::all('id', 'company_name');


		if ($logged_user->can('report-employee'))
		{
			if (request()->ajax())
			{
				if (!empty($request->designation_id))
				{
					$employees = Employee::with('company:id,company_name', 'user:id,username',
						'department:id,department_name', 'designation:id,designation_name')
						->where('designation_id', $request->designation_id)
                        ->where('is_active',1)->where('exit_date',NULL)
						->get();
				} elseif (!empty($request->department_id))
				{
					$employees = Employee::with('company:id,company_name', 'user:id,username',
						'department:id,department_name', 'designation:id,designation_name')
						->where('department_id', $request->department_id)
                        ->where('is_active',1)->where('exit_date',NULL)
						->get();
				} elseif (!empty($request->company_id))
				{
					$employees = Employee::with('company:id,company_name', 'user:id,username',
						'department:id,department_name', 'designation:id,designation_name')
						->where('company_id', $request->company_id)
                        ->where('is_active',1)->where('exit_date',NULL)
						->get();
				} else
				{
					$employees = Employee::with('company:id,company_name', 'user:id,username',
						'department:id,department_name', 'designation:id,designation_name')
                        ->where('is_active',1)->where('exit_date',NULL)
						->get();
				}


				return datatables()->of($employees)
					->setRowId(function ($employee)
					{
						return $employee->id;
					})
					->addColumn('username', function ($row)
					{
						return $username = $row->user->username ?? '---';

					})
					->addColumn('name', function ($row)
					{
						return $row->full_name ?? '';
					})
					->addColumn('company', function ($row)
					{
						return $row->company->company_name ?? '';
					})
					->addColumn('department', function ($row)
					{
						return $row->department->department_name ?? '';
					})
					->addColumn('designation', function ($row)
					{
						return $row->designation->designation_name ?? '';
					})
					->make(true);
			}

			return view('report.employees_report', compact('companies'));
		}

		return abort('403', __('You are not authorized'));
	}

	public function account(Request $request)
	{

		$logged_user = auth()->user();
		$accounts = FinanceBankCash::all('id', 'account_name');
		$start_date = Carbon::parse($request->filter_start_date)->format('Y-m-d') ?? '';
		$end_date = Carbon::parse($request->filter_end_date)->format('Y-m-d') ?? '';


		if ($logged_user->can('report-account'))
		{
			if (request()->ajax())
			{
				if (!empty($request->account_id))
				{
					$transactions = FinanceTransaction::where('account_id', $request->account_id)
						->where(function ($query) use ($start_date, $end_date)
						{
							$query->whereBetween('deposit_date', [$start_date, $end_date])
								->OrWhereBetween('expense_date', [$start_date, $end_date]);
						})
						->get();
				} else
				{
					$transactions = [];
				}

				return datatables()->of($transactions)
					->setRowId(function ($transaction)
					{
						return $transaction->id;
					})
					->addColumn('transaction_date', function ($row)
					{
						return empty($row->expense_reference) ? $row->deposit_date : $row->expense_date;
					})
					->addColumn('type', function ($row)
					{
						if ($row->category == 'transfer')
						{
							return trans('file.Transfer');
						} else
						{
							return $row->expense_reference ? trans('file.Expense') : trans('file.Income');
						}
					})
					->addColumn('reference_no', function ($row)
					{
						return empty($row->expense_reference) ? $row->deposit_reference : $row->expense_reference;
					})
					->addColumn('credit', function ($row)
					{
						if ($row->deposit_reference)
						{
							return $row->amount;
						} else
						{
							return '0.00';
						}
					})
					->addColumn('debit', function ($row)
					{
						if ($row->expense_reference)
						{
							return $row->amount;
						} else
						{
							return '0.00';
						}
					})
					->make(true);
			}

			return view('report.account_report', compact('accounts'));
		}

		return abort('403', __('You are not authorized'));
	}

	public function expense(Request $request)
	{
		$logged_user = auth()->user();

		$categories = ExpenseType::select('id', 'type')->get();

		$start_date = empty($request->filter_start_date) ? '' : Carbon::parse($request->filter_start_date)->format('Y-m-d');
		$end_date = empty($request->filter_end_date) ? '' : Carbon::parse($request->filter_end_date)->format('Y-m-d') ;



		if ($request->category_id)
		{
			$expenses = FinanceExpense::with('Account:id,account_name', 'Payee:id,payee_name', 'Category:id,type')
				->where('category_id', $request->category_id)
				->whereBetween('expense_date', [$start_date, $end_date])
				->get();
		}
		else {
			$expenses = FinanceExpense::with('Account:id,account_name', 'Payee:id,payee_name', 'Category:id,type')
				->whereBetween('expense_date', [$start_date, $end_date])
				->get();
		}

		if ($logged_user->can('report-expense'))
		{
			if (request()->ajax())
			{
				return datatables()->of($expenses)
					->setRowId(function ($expense)
					{
						return $expense->id;
					})
					->addColumn('account', function ($row)
					{
						return empty($row->Account->account_name) ? '' : $row->Account->account_name;
					})
					->addColumn('payee', function ($row)
					{
						return empty($row->Payee->payee_name) ? '' : $row->Payee->payee_name;
					})
					->addColumn('category', function ($row)
					{
						return empty($row->Category->type) ? '' : $row->Category->type;
					})
					->make(true);
			}

			return view('report.expense_report', compact('categories'));
		}

		return abort('403', __('You are not authorized'));
	}

	public function deposit(Request $request)
	{
		$logged_user = auth()->user();

		$start_date = empty($request->filter_start_date) ? '' : Carbon::parse($request->filter_start_date)->format('Y-m-d');
		$end_date = empty($request->filter_end_date) ? '' : Carbon::parse($request->filter_end_date)->format('Y-m-d') ;

		if ($request->category)
		{
			$deposits = FinanceDeposit::with('Account:id,account_name', 'Payer:id,payer_name')
				->where('category', $request->category)
				->whereBetween('deposit_date', [$start_date, $end_date])
				->get();
		}
		else {
			$deposits = FinanceDeposit::with('Account:id,account_name', 'Payer:id,payer_name')
				->whereBetween('deposit_date', [$start_date, $end_date])
				->get();
		}

		if ($logged_user->can('report-deposit'))
		{
			if (request()->ajax())
			{
				return datatables()->of($deposits)
					->setRowId(function ($deposit)
					{
						return $deposit->id;
					})
					->addColumn('account', function ($row)
					{
						return empty($row->Account->account_name) ? '' : $row->Account->account_name;
					})
					->addColumn('payer', function ($row)
					{
						return empty($row->Payer->payer_name) ? '' : $row->Payer->payer_name;
					})
					->addColumn('category', function ($row)
					{
						return empty($row->category) ? '' : $row->category;
					})
					->make(true);
			}

			return view('report.deposit_report');
		}

		return abort('403', __('You are not authorized'));
	}

	public function transaction(Request $request)
	{
		$logged_user = auth()->user();

		if ($logged_user->can('report-transaction'))
		{
			if (request()->ajax())
			{
				$start_date = empty($request->filter_start_date) ? '' : Carbon::parse($request->filter_start_date)->format('Y-m-d');
				$end_date = empty($request->filter_end_date) ? '' : Carbon::parse($request->filter_end_date)->format('Y-m-d') ;

				$transactions = FinanceTransaction::with('Account:id,account_name')
					->whereBetween('deposit_date', [$start_date, $end_date])
					->orWhereBetween('expense_date',[$start_date, $end_date])
					->get();
				return datatables()->of($transactions)
					->setRowId(function ($transaction)
					{
						return $transaction->id;
					})
					->addColumn('account', function ($row)
					{
						$button = '<h6><a href="' . route('transactions.show', $row->Account->id) . '">' . $row->Account->account_name . '</a></h6>';

						return $button;
					})
					->addColumn('date', function ($row)
					{
						return empty($row->expense_reference) ? $row->deposit_date : $row->expense_date;
					})
					->addColumn('ref_no', function ($row)
					{
						return empty($row->expense_reference) ? $row->deposit_reference : $row->expense_reference;
					})
					->rawColumns(['account'])
					->make(true);
			}
			return view('report.transaction_report');
		}
		return abort('403', __('You are not authorized'));
	}

    public function pension(Request $request)
    {
        $logged_user = auth()->user();
        $companies = company::all();
        $selected_date = empty($request->filter_month_year) ? now()->format('F-Y') : $request->filter_month_year ;


        if (request()->ajax())
		{
            // $payslips = Payslip::with( ['employee:id,first_name,last_name'])
            //             ->where('month_year',$selected_date)
            //             ->where('pension_type','!=',NULL)
            //             ->get();

            if (!empty($request->filter_employee))
            {
                $payslips = Payslip::with(['employee:id,first_name,last_name'])
                        ->where('employee_id', $request->filter_employee)
                        ->where('month_year', $selected_date)
                        ->where('pension_type','!=',NULL)
                        ->get();
            }
            elseif (!empty($request->filter_company)) {
                $payslips = Payslip::with(['employee:id,first_name,last_name'])
                        ->where('company_id', $request->filter_company)
                        ->where('month_year', $selected_date)
                        ->where('pension_type','!=',NULL)
                        ->get();
            }
            else {
                $payslips = Payslip::with( ['employee:id,first_name,last_name'])
                        ->where('month_year',$selected_date)
                        ->where('pension_type','!=',NULL)
                        ->latest('created_at')
                        ->get();
            }

            return datatables()->of($payslips)
					->setRowId(function ($payslip)
					{
						return $payslip->id;
					})
                    ->addColumn('employee_name', function ($row)
					{
						return $row->employee->full_name;
					})
                    ->addColumn('pension_amount', function ($row)
					{
                        if($row->pension_type=='percentage')
                        {
                            return '% '.$row->pension_amount;
                        }
                        else{
                            return config('variable.currency').' '.$row->pension_amount;
                        }

					})
                    ->addColumn('remaining', function ($row)
					{
                        if ($row->pension_type=='percentage') {
                            $remaining = $row->basic_salary - (($row->basic_salary * $row->pension_amount) /100);
                        } else {
                            $remaining = $row->basic_salary - $row->pension_amount;
                        }

						return config('variable.currency').' '.$remaining;
					})
					->make(true);

        }

        return view('report.pension_report',compact('companies'));
    }

    protected function distanceInKilometers(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): ?float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }

        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(($earthRadius * $c) / 1000, 2);
    }

    public function loginLocations(Request $request)
    {
        $logged_user = auth()->user();

        if (! ManagedEmployeeScope::canAccessClockInLocationReport((int) $logged_user->id, (int) $logged_user->role_users_id)) {
            return abort(403, __('You are not authorized'));
        }

        $useManagedScope = ManagedEmployeeScope::canAccessScopedEmployeeList((int) $logged_user->id, (int) $logged_user->role_users_id)
            && ! $logged_user->can('report-employee');
        $managedEmployeeIds = $useManagedScope
            ? ManagedEmployeeScope::managedEmployeeIds((int) $logged_user->id)
            : [];

        $isLocationHead = location::userIsLocationHead((int) $logged_user->id);
        $companies = $logged_user->can('report-employee')
            ? CompanyScope::companiesForSelect()
            : ($isLocationHead
                ? CompanyScope::companiesForLocationHead((int) $logged_user->id)
                : CompanyScope::companiesForSelect());

        if ($companies->isEmpty()) {
            $companies = CompanyScope::companiesForSelect();
        }

        if (request()->ajax()) {
            $attendances = Attendance::with([
                'employee:id,first_name,last_name,company_id,location_id,client_id,attendance_type',
                'employee.company:id,company_name',
                'employee.location:id,location_name,latitude,longitude,max_radius',
                'employee.user:id,username',
            ])
                ->whereNotNull('clock_in')
                ->orderByDesc('id');

            if ($useManagedScope) {
                if ($managedEmployeeIds === []) {
                    $attendances->whereRaw('1 = 0');
                } else {
                    $attendances->whereIn('employee_id', $managedEmployeeIds);
                }
            }

            if (CompanyScope::applies()) {
                $companyId = CompanyScope::companyId();
                if ($companyId) {
                    $attendances->whereHas('employee', function ($query) use ($companyId) {
                        $query->where('company_id', $companyId);
                    });
                } else {
                    $attendances->whereRaw('1 = 0');
                }
            }

            if ($request->filled('company_id')) {
                $attendances->whereHas('employee', function ($query) use ($request) {
                    $query->where('company_id', (int) $request->company_id);
                });
            }

            if ($request->filled('client_id')) {
                $attendances->whereHas('employee', function ($query) use ($request) {
                    $query->where('client_id', (int) $request->client_id);
                });
            }

            if ($request->filled('location_id')) {
                $attendances->whereHas('employee', function ($query) use ($request) {
                    $query->where('location_id', (int) $request->location_id);
                });
            }

            if ($request->filled('employee_id')) {
                $attendances->where('employee_id', (int) $request->employee_id);
            }

            if ($request->filled('filter_date')) {
                $attendances->whereDate('attendance_date', Carbon::parse($request->filter_date)->format('Y-m-d'));
            }

            $filterCompanyId = $request->filled('company_id') ? (int) $request->company_id : null;
            $officeLocationCache = [];

            $officeLocationsFor = function (?int $companyId) use (&$officeLocationCache, $filterCompanyId) {
                $key = $companyId ?? 0;

                if (! array_key_exists($key, $officeLocationCache)) {
                    $officeLocationCache[$key] = NearestOfficeLocation::candidates($companyId ?? $filterCompanyId);
                }

                return $officeLocationCache[$key];
            };

            return datatables()->of($attendances)
                ->addColumn('employee_name', function ($row) {
                    return optional($row->employee)->full_name ?? '---';
                })
                ->addColumn('username', function ($row) {
                    return optional(optional($row->employee)->user)->username ?? '---';
                })
                ->addColumn('company', function ($row) {
                    return optional(optional($row->employee)->company)->company_name ?? '---';
                })
                ->addColumn('clock_in_at', function ($row) {
                    $date = $row->getRawOriginal('attendance_date')
                        ? Carbon::parse($row->getRawOriginal('attendance_date'))->format(env('Date_Format'))
                        : '---';

                    return trim($date.' '.($row->clock_in ?? ''));
                })
                ->addColumn('ip_address', function ($row) {
                    return $row->clock_in_ip ?? '---';
                })
                ->addColumn('attendance_type', function ($row) {
                    $type = strtolower(trim((string) optional($row->employee)->attendance_type));

                    return match ($type) {
                        'ip_based' => __('IP Based'),
                        'location_based' => __('Location Based'),
                        'general' => __('General'),
                        default => $type !== '' ? ucwords(str_replace('_', ' ', $type)) : '---',
                    };
                })
                ->addColumn('user_latitude', function ($row) {
                    return $row->clock_in_latitude !== null
                        ? number_format((float) $row->clock_in_latitude, 7)
                        : '---';
                })
                ->addColumn('user_longitude', function ($row) {
                    return $row->clock_in_longitude !== null
                        ? number_format((float) $row->clock_in_longitude, 7)
                        : '---';
                })
                ->addColumn('user_place_name', function ($row) {
                    $lat = $row->clock_in_latitude !== null ? (float) $row->clock_in_latitude : null;
                    $lng = $row->clock_in_longitude !== null ? (float) $row->clock_in_longitude : null;

                    if ($lat === null || $lng === null) {
                        return '---';
                    }

                    $placeName = ReverseGeocoder::cachedPlaceName($lat, $lng);

                    if ($placeName) {
                        return e($placeName);
                    }

                    return '<span class="geo-place-pending text-muted" data-lat="'.$lat.'" data-lng="'.$lng.'">'.e(__('Resolving...')).'</span>';
                })
                ->addColumn('location_name', function ($row) {
                    return optional(optional($row->employee)->location)->location_name ?? '---';
                })
                ->addColumn('location_latitude', function ($row) {
                    $lat = optional(optional($row->employee)->location)->latitude;

                    return $lat !== null ? number_format((float) $lat, 6) : '---';
                })
                ->addColumn('location_longitude', function ($row) {
                    $lng = optional(optional($row->employee)->location)->longitude;

                    return $lng !== null ? number_format((float) $lng, 6) : '---';
                })
                ->addColumn('nearest_location_name', function ($row) use ($officeLocationsFor) {
                    $userLat = $row->clock_in_latitude !== null ? (float) $row->clock_in_latitude : null;
                    $userLng = $row->clock_in_longitude !== null ? (float) $row->clock_in_longitude : null;
                    $companyId = optional($row->employee)->company_id ? (int) $row->employee->company_id : null;
                    $nearest = NearestOfficeLocation::find(
                        $userLat,
                        $userLng,
                        $companyId,
                        $officeLocationsFor($companyId)
                    );

                    if ($nearest === null) {
                        return '---';
                    }

                    return e($nearest['name']).' ('.$nearest['distance_km'].' km)';
                })
                ->addColumn('distance_km', function ($row) {
                    $userLat = $row->clock_in_latitude !== null ? (float) $row->clock_in_latitude : null;
                    $userLng = $row->clock_in_longitude !== null ? (float) $row->clock_in_longitude : null;
                    $location = optional($row->employee)->location;
                    $distance = $this->distanceInKilometers(
                        $userLat,
                        $userLng,
                        $location && $location->latitude !== null ? (float) $location->latitude : null,
                        $location && $location->longitude !== null ? (float) $location->longitude : null
                    );

                    if ($distance === null) {
                        return '---';
                    }

                    $maxRadius = $location && $location->max_radius !== null
                        ? round(((float) $location->max_radius) / 1000, 2)
                        : null;
                    $label = $distance.' km';

                    if ($maxRadius !== null && $distance > $maxRadius) {
                        $label .= ' ('.__('outside geofence').')';
                    }

                    return $label;
                })
                ->addColumn('map_action', function ($row) use ($officeLocationsFor) {
                    $userLat = $row->clock_in_latitude !== null ? (float) $row->clock_in_latitude : null;
                    $userLng = $row->clock_in_longitude !== null ? (float) $row->clock_in_longitude : null;

                    if ($userLat === null || $userLng === null) {
                        return '---';
                    }

                    $employee = $row->employee;
                    $office = optional($employee)->location;
                    $companyId = optional($employee)->company_id ? (int) $employee->company_id : null;
                    $nearest = NearestOfficeLocation::find(
                        $userLat,
                        $userLng,
                        $companyId,
                        $officeLocationsFor($companyId)
                    );

                    $placeName = ReverseGeocoder::cachedPlaceName($userLat, $userLng) ?? '';

                    return '<button type="button" class="btn btn-sm btn-info btn-view-clockin-map"'
                        .' data-employee="'.e(optional($employee)->full_name ?? '---').'"'
                        .' data-user-lat="'.$userLat.'"'
                        .' data-user-lng="'.$userLng.'"'
                        .' data-user-place="'.e($placeName).'"'
                        .' data-office-name="'.e(optional($office)->location_name ?? '').'"'
                        .' data-office-lat="'.($office && $office->latitude !== null ? (float) $office->latitude : '').'"'
                        .' data-office-lng="'.($office && $office->longitude !== null ? (float) $office->longitude : '').'"'
                        .' data-nearest-name="'.e($nearest['name'] ?? '').'"'
                        .' data-nearest-lat="'.($nearest['latitude'] ?? '').'"'
                        .' data-nearest-lng="'.($nearest['longitude'] ?? '').'"'
                        .' data-nearest-distance="'.($nearest['distance_km'] ?? '').'"'
                        .'><i class="fa fa-map-marker"></i> '.__('Map').'</button>';
                })
                ->rawColumns(['user_place_name', 'map_action'])
                ->make(true);
        }

        return view('report.login_locations', compact('companies'));
    }

    public function reverseGeocode(Request $request)
    {
        $logged_user = auth()->user();

        if (! ManagedEmployeeScope::canAccessClockInLocationReport((int) $logged_user->id, (int) $logged_user->role_users_id)) {
            return abort(403, __('You are not authorized'));
        }

        $lat = is_numeric($request->lat) ? (float) $request->lat : null;
        $lng = is_numeric($request->lng) ? (float) $request->lng : null;

        if ($lat === null || $lng === null) {
            return response()->json(['place_name' => null]);
        }

        return response()->json([
            'place_name' => ReverseGeocoder::placeName($lat, $lng),
        ]);
    }

    public function storeReverseGeocode(Request $request)
    {
        $logged_user = auth()->user();

        if (! ManagedEmployeeScope::canAccessClockInLocationReport((int) $logged_user->id, (int) $logged_user->role_users_id)) {
            return abort(403, __('You are not authorized'));
        }

        $lat = is_numeric($request->lat) ? (float) $request->lat : null;
        $lng = is_numeric($request->lng) ? (float) $request->lng : null;
        $placeName = trim((string) $request->place_name);

        if ($lat === null || $lng === null || $placeName === '') {
            return response()->json(['saved' => false]);
        }

        ReverseGeocoder::rememberPlaceName($lat, $lng, $placeName);

        return response()->json(['saved' => true]);
    }

    public function summaryDashboard(Request $request)
    {
        $logged_user = auth()->user();

        if (! $logged_user->can('report-project')) {
            return abort(403, __('You are not authorized'));
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($this->buildSummaryDashboardData($request));
        }

        return view('report.summary_dashboard');
    }

    public function summaryDashboardPdf(Request $request)
    {
        $logged_user = auth()->user();

        if (! $logged_user->can('report-project')) {
            return abort(403, __('You are not authorized'));
        }

        $data = $this->buildSummaryDashboardData($request);

        PDF::setOptions([
            'dpi' => 120,
            'defaultFont' => 'sans-serif',
            'tempDir' => storage_path('temp'),
            'isHtml5ParserEnabled' => true,
        ]);

        $pdf = PDF::loadView('report.summary_dashboard_pdf', [
            'data' => $data,
            'generatedAt' => now()->format(env('Date_Format').' H:i'),
            'filterSummary' => $this->summaryDashboardFilterSummary($request, $data),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('operations-summary-'.now()->format('Y-m-d-His').'.pdf');
    }

    public function summaryDashboardCsv(Request $request): StreamedResponse
    {
        $logged_user = auth()->user();

        if (! $logged_user->can('report-project')) {
            abort(403, __('You are not authorized'));
        }

        $data = $this->buildSummaryDashboardData($request);
        $statusLabels = [
            'in_progress' => __('In Progress'),
            'not_started' => __('Not Started'),
            'completed' => __('Completed'),
            'deferred' => __('Deferred'),
        ];

        $filename = 'operations-summary-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($data, $statusLabels) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                __('Employee'),
                __('Staff ID'),
                trans('file.Company'),
                trans('file.Client'),
                trans('file.Location'),
                trans('file.Department'),
                trans('file.Designation'),
                trans('file.Project'),
                trans('file.Status'),
            ]);

            foreach ($data['deployments'] as $deployment) {
                $projects = $deployment['projects'] ?? [];

                if ($projects === []) {
                    fputcsv($handle, [
                        $deployment['employee_name'],
                        $deployment['staff_id'],
                        $deployment['company'],
                        $deployment['client'],
                        $deployment['location'],
                        $deployment['department'],
                        $deployment['designation'],
                        '---',
                        '---',
                    ]);

                    continue;
                }

                foreach ($projects as $project) {
                    $status = strtolower((string) ($project['status'] ?? ''));
                    $status = str_replace(' ', '_', $status);

                    fputcsv($handle, [
                        $deployment['employee_name'],
                        $deployment['staff_id'],
                        $project['company'] ?? $deployment['company'],
                        $deployment['client'],
                        $deployment['location'],
                        $project['department'] ?? $deployment['department'],
                        $deployment['designation'],
                        $project['title'] ?? '---',
                        $statusLabels[$status] ?? ($project['status'] ?? '---'),
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function summaryDashboardFilterSummary(Request $request, array $data): string
    {
        $parts = [];

        if ($request->filled('company_id')) {
            $company = collect($data['filters']['companies'] ?? [])->firstWhere('id', (int) $request->company_id);
            $parts[] = trans('file.Company').': '.($company['name'] ?? __('Selected'));
        }

        if ($request->filled('client_id')) {
            $client = collect($data['filters']['clients'] ?? [])->firstWhere('id', (int) $request->client_id);
            $parts[] = trans('file.Client').': '.($client['name'] ?? __('Selected'));
        }

        if ($request->filled('department_id')) {
            $department = collect($data['filters']['departments'] ?? [])->firstWhere('id', (int) $request->department_id);
            $parts[] = trans('file.Department').': '.($department['name'] ?? __('Selected'));
        }

        if ($request->filled('location_id')) {
            $location = collect($data['filters']['locations'] ?? [])->firstWhere('id', (int) $request->location_id);
            $parts[] = trans('file.Location').': '.($location['name'] ?? __('Selected'));
        }

        if ($request->filled('project_status')) {
            $status = collect($data['filters']['statuses'] ?? [])->firstWhere('value', $request->project_status);
            $parts[] = trans('file.Status').': '.($status['label'] ?? $request->project_status);
        }

        return $parts !== [] ? implode(' | ', $parts) : __('All records');
    }

    protected function buildSummaryDashboardData(Request $request): array
    {
        $runningStatuses = ['in_progress', 'not_started', 'not started'];

        $projectsQuery = Project::query()
            ->select('id', 'title', 'client_id', 'company_id', 'department_id', 'project_status', 'project_progress')
            ->with([
                'client:id,company_name,first_name,last_name,is_active',
                'company:id,company_name',
                'department:id,department_name',
            ]);

        if (CompanyScope::applies()) {
            $companyId = CompanyScope::companyId();

            if ($companyId) {
                $projectsQuery->where('company_id', $companyId);
            } else {
                $projectsQuery->whereRaw('1 = 0');
            }
        }

        if ($request->filled('company_id')) {
            $projectsQuery->where('company_id', (int) $request->company_id);
        }

        if ($request->filled('client_id')) {
            $projectsQuery->where('client_id', (int) $request->client_id);
        }

        if ($request->filled('department_id')) {
            $projectsQuery->where('department_id', (int) $request->department_id);
        }

        if ($request->filled('project_status')) {
            $projectsQuery->where('project_status', $request->project_status);
        }

        $projects = $projectsQuery->orderBy('title')->get();

        $companiesQuery = company::query()
            ->select('id', 'company_name')
            ->orderBy('company_name');

        if (CompanyScope::applies()) {
            $scopedCompanyId = CompanyScope::companyId();

            if ($scopedCompanyId) {
                $companiesQuery->where('id', $scopedCompanyId);
            } else {
                $companiesQuery->whereRaw('1 = 0');
            }
        }

        if ($request->filled('company_id')) {
            $companiesQuery->where('id', (int) $request->company_id);
        }

        $companies = $companiesQuery->get();

        $clientsQuery = Client::query()
            ->select('id', 'company_name', 'first_name', 'last_name', 'is_active')
            ->whereIn('id', $projects->pluck('client_id')->filter()->unique()->values())
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($request->filled('client_id')) {
            $clientsQuery->where('id', (int) $request->client_id);
        }

        $clients = $clientsQuery->get();
        $clientIds = $clients->pluck('id');

        $locationsQuery = location::query()
            ->select('id', 'location_name', 'client_id', 'city')
            ->orderBy('location_name');

        if ($request->filled('company_id')) {
            $companyId = (int) $request->company_id;
            $locationsQuery->where(function ($query) use ($companyId, $clientIds) {
                $query->whereHas('companies', function ($companyQuery) use ($companyId) {
                    $companyQuery->where('companies.id', $companyId);
                });

                if ($clientIds->isNotEmpty()) {
                    $query->orWhereIn('client_id', $clientIds);
                }
            });
        } elseif ($clientIds->isNotEmpty()) {
            $locationsQuery->whereIn('client_id', $clientIds);
        }

        if ($request->filled('client_id')) {
            $locationsQuery->where('client_id', (int) $request->client_id);
        }

        $locations = $locationsQuery->get();

        $employeeQuery = Employee::query()
            ->select(
                'id',
                'first_name',
                'last_name',
                'staff_id',
                'location_id',
                'department_id',
                'designation_id',
                'client_id',
                'company_id'
            )
            ->with([
                'location:id,location_name',
                'department:id,department_name',
                'designation:id,designation_name',
                'client:id,company_name,first_name,last_name',
                'company:id,company_name',
                'projects' => function ($query) use ($projects) {
                    $query->select('projects.id', 'projects.title', 'projects.client_id', 'projects.company_id', 'projects.department_id', 'projects.project_status')
                        ->whereIn('projects.id', $projects->pluck('id'))
                        ->with([
                            'client:id,company_name,first_name,last_name',
                            'company:id,company_name',
                            'department:id,department_name',
                        ]);
                },
            ])
            ->where('is_active', 1)
            ->whereNull('exit_date')
            ->whereHas('projects', function ($query) use ($projects) {
                $query->whereIn('projects.id', $projects->pluck('id'));
            });

        if (ManagedEmployeeScope::canAccessScopedEmployeeList((int) auth()->id(), (int) auth()->user()->role_users_id)) {
            $managedIds = ManagedEmployeeScope::managedEmployeeIds((int) auth()->id());
            $employeeQuery->whereIn('id', $managedIds ?: [-1]);
        }

        if ($request->filled('company_id')) {
            $employeeQuery->where('company_id', (int) $request->company_id);
        }

        if ($request->filled('client_id')) {
            $employeeQuery->where('client_id', (int) $request->client_id);
        }

        if ($request->filled('department_id')) {
            $employeeQuery->where('department_id', (int) $request->department_id);
        }

        if ($request->filled('location_id')) {
            $employeeQuery->where('location_id', (int) $request->location_id);
        }

        $deployedEmployees = $employeeQuery->orderBy('first_name')->get();

        $normalizeStatus = static function (?string $status): string {
            $status = strtolower(trim((string) $status));

            if (in_array($status, ['not started', 'not_started'], true)) {
                return 'not_started';
            }

            return $status !== '' ? $status : 'not_started';
        };

        $clientSummaries = $clients->map(function (Client $client) use ($projects, $locations, $deployedEmployees, $normalizeStatus, $runningStatuses) {
            $clientProjects = $projects->where('client_id', $client->id)->values();
            $clientLocations = $locations->where('client_id', $client->id)->values();
            $clientEmployees = $deployedEmployees->where('client_id', $client->id)->values();

            $statusCounts = [
                'in_progress' => 0,
                'not_started' => 0,
                'completed' => 0,
                'deferred' => 0,
            ];

            foreach ($clientProjects as $project) {
                $status = $normalizeStatus($project->project_status);

                if (isset($statusCounts[$status])) {
                    $statusCounts[$status]++;
                }
            }

            $departmentBreakdown = $clientProjects
                ->groupBy(fn ($project) => optional($project->department)->department_name ?: __('No Department'))
                ->map(fn ($items, $name) => [
                    'department' => $name,
                    'project_count' => $items->count(),
                    'running_count' => $items->filter(fn ($project) => in_array($normalizeStatus($project->project_status), $runningStatuses, true))->count(),
                ])
                ->values();

            return [
                'id' => $client->id,
                'name' => ClientDisplay::label($client),
                'organization' => ClientDisplay::organization($client),
                'is_active' => (bool) $client->is_active,
                'total_projects' => $clientProjects->count(),
                'running_projects' => $clientProjects->filter(fn ($project) => in_array($normalizeStatus($project->project_status), $runningStatuses, true))->count(),
                'active_projects' => $clientProjects->filter(fn ($project) => $normalizeStatus($project->project_status) === 'in_progress')->count(),
                'locations_count' => $clientLocations->count(),
                'deployed_resources' => $clientEmployees->count(),
                'status_counts' => $statusCounts,
                'departments' => $departmentBreakdown,
                'locations' => $clientLocations->map(fn ($location) => [
                    'id' => $location->id,
                    'name' => $location->location_name,
                    'city' => $location->city,
                    'deployed_count' => $clientEmployees->where('location_id', $location->id)->count(),
                ])->values(),
                'projects' => $clientProjects->map(fn ($project) => [
                    'id' => $project->id,
                    'title' => $project->title,
                    'company' => optional($project->company)->company_name ?: __('No Company'),
                    'status' => $normalizeStatus($project->project_status),
                    'status_label' => ucwords(str_replace('_', ' ', $normalizeStatus($project->project_status))),
                    'department' => optional($project->department)->department_name ?: __('No Department'),
                    'progress' => (int) ($project->project_progress ?? 0),
                    'deployed_count' => $deployedEmployees->filter(fn ($employee) => $employee->projects->contains('id', $project->id))->count(),
                ])->values(),
            ];
        })->values();

        $companySummaries = $companies->map(function ($company) use ($projects, $clients, $deployedEmployees, $normalizeStatus, $runningStatuses) {
            $companyProjects = $projects->where('company_id', $company->id)->values();
            $companyClientIds = $companyProjects->pluck('client_id')->filter()->unique();
            $companyClients = $clients->whereIn('id', $companyClientIds)->values();
            $companyEmployees = $deployedEmployees->where('company_id', $company->id);

            return [
                'id' => $company->id,
                'name' => $company->company_name,
                'total_projects' => $companyProjects->count(),
                'running_projects' => $companyProjects->filter(fn ($project) => in_array($normalizeStatus($project->project_status), $runningStatuses, true))->count(),
                'clients_count' => $companyClients->count(),
                'deployed_resources' => $companyEmployees->count(),
                'clients' => $companyClients->map(function (Client $client) use ($company, $companyProjects, $deployedEmployees, $normalizeStatus) {
                    $clientProjects = $companyProjects->where('client_id', $client->id)->values();
                    $clientEmployees = $deployedEmployees
                        ->where('company_id', $company->id)
                        ->where('client_id', $client->id);

                    return [
                        'id' => $client->id,
                        'name' => ClientDisplay::label($client),
                        'projects_count' => $clientProjects->count(),
                        'deployed_resources' => $clientEmployees->count(),
                        'projects' => $clientProjects->map(function ($project) use ($deployedEmployees, $normalizeStatus) {
                            $projectEmployees = $deployedEmployees->filter(
                                fn ($employee) => $employee->projects->contains('id', $project->id)
                            );

                            return [
                                'id' => $project->id,
                                'title' => $project->title,
                                'status' => $normalizeStatus($project->project_status),
                                'status_label' => ucwords(str_replace('_', ' ', $normalizeStatus($project->project_status))),
                                'department' => optional($project->department)->department_name ?: __('No Department'),
                                'deployed_count' => $projectEmployees->count(),
                                'employees' => $projectEmployees->map(fn (Employee $employee) => [
                                    'id' => $employee->id,
                                    'name' => $employee->full_name,
                                    'staff_id' => $employee->staff_id ?: '---',
                                    'designation' => optional($employee->designation)->designation_name ?: '---',
                                    'department' => optional($employee->department)->department_name ?: '---',
                                    'location' => optional($employee->location)->location_name ?: '---',
                                ])->values(),
                            ];
                        })->values(),
                    ];
                })
                    ->sortByDesc('projects_count')
                    ->values(),
            ];
        })->values();

        $departmentSummary = $projects
            ->groupBy(fn ($project) => optional($project->department)->department_name ?: __('No Department'))
            ->map(function ($items, $name) use ($deployedEmployees, $normalizeStatus, $runningStatuses) {
                return [
                    'department' => $name,
                    'project_count' => $items->count(),
                    'running_count' => $items->filter(fn ($project) => in_array($normalizeStatus($project->project_status), $runningStatuses, true))->count(),
                    'deployed_count' => $deployedEmployees->filter(function (Employee $employee) use ($name) {
                        $departmentName = optional($employee->department)->department_name ?: __('No Department');

                        return $departmentName === $name;
                    })->count(),
                ];
            })
            ->values();

        $locationSummary = $locations
            ->map(function ($location) use ($deployedEmployees) {
                $resources = $deployedEmployees->where('location_id', $location->id);

                return [
                    'id' => $location->id,
                    'name' => $location->location_name,
                    'client_id' => $location->client_id,
                    'deployed_count' => $resources->count(),
                    'project_count' => $resources
                        ->flatMap(fn ($employee) => $employee->projects->pluck('id'))
                        ->unique()
                        ->count(),
                ];
            })
            ->values();

        $deployments = $deployedEmployees->map(function (Employee $employee) {
            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'staff_id' => $employee->staff_id ?: '---',
                'company' => optional($employee->company)->company_name ?: '---',
                'client' => ClientDisplay::label($employee->client),
                'client_id' => $employee->client_id,
                'location' => optional($employee->location)->location_name ?: '---',
                'location_id' => $employee->location_id,
                'department' => optional($employee->department)->department_name ?: '---',
                'designation' => optional($employee->designation)->designation_name ?: '---',
                'projects' => $employee->projects->map(fn ($project) => [
                    'id' => $project->id,
                    'title' => $project->title,
                    'status' => $project->project_status,
                    'department' => optional($project->department)->department_name ?: __('No Department'),
                    'company' => optional($project->company)->company_name ?: '---',
                    'client' => ClientDisplay::label($project->client),
                ])->values(),
                'project_names' => $employee->projects->pluck('title')->implode(', ') ?: '---',
            ];
        })->values();

        $statusTotals = [
            'in_progress' => 0,
            'not_started' => 0,
            'completed' => 0,
            'deferred' => 0,
        ];

        foreach ($projects as $project) {
            $status = $normalizeStatus($project->project_status);

            if (isset($statusTotals[$status])) {
                $statusTotals[$status]++;
            }
        }

        $filterOptions = [
            'companies' => $companies->map(fn ($company) => [
                'id' => $company->id,
                'name' => $company->company_name,
            ])->values(),
            'clients' => $clients->map(fn ($client) => [
                'id' => $client->id,
                'name' => ClientDisplay::label($client),
            ])->values(),
            'departments' => department::query()
                ->select('id', 'department_name')
                ->whereIn('id', $projects->pluck('department_id')->filter()->unique())
                ->orderBy('department_name')
                ->get()
                ->map(fn ($department) => [
                    'id' => $department->id,
                    'name' => $department->department_name,
                ])
                ->values(),
            'locations' => $locations->map(fn ($location) => [
                'id' => $location->id,
                'name' => $location->location_name,
                'client_id' => $location->client_id,
            ])->values(),
            'statuses' => [
                ['value' => 'in_progress', 'label' => __('In Progress')],
                ['value' => 'not_started', 'label' => __('Not Started')],
                ['value' => 'completed', 'label' => __('Completed')],
                ['value' => 'deferred', 'label' => __('Deferred')],
            ],
        ];

        return [
            'totals' => [
                'companies' => $companies->count(),
                'clients' => $clients->count(),
                'active_clients' => $clients->where('is_active', true)->count(),
                'projects' => $projects->count(),
                'running_projects' => $projects->filter(fn ($project) => in_array($normalizeStatus($project->project_status), $runningStatuses, true))->count(),
                'active_projects' => $projects->filter(fn ($project) => $normalizeStatus($project->project_status) === 'in_progress')->count(),
                'locations' => $locations->count(),
                'deployed_resources' => $deployedEmployees->count(),
            ],
            'project_status_totals' => $statusTotals,
            'charts' => [
                'pipeline' => [
                    'labels' => [
                        __('Companies'),
                        __('Clients'),
                        __('Projects'),
                        __('Deployed Employees'),
                    ],
                    'values' => [
                        $companies->count(),
                        $clients->count(),
                        $projects->count(),
                        $deployedEmployees->count(),
                    ],
                ],
                'deployment_by_company' => $companySummaries->map(fn ($company) => [
                    'name' => $company['name'],
                    'deployed_count' => $company['deployed_resources'],
                    'projects_count' => $company['total_projects'],
                ])->values(),
                'deployment_by_location' => $locationSummary
                    ->sortByDesc('deployed_count')
                    ->take(8)
                    ->values(),
            ],
            'companies' => $companySummaries,
            'clients' => $clientSummaries,
            'departments' => $departmentSummary,
            'locations' => $locationSummary,
            'deployments' => $deployments,
            'filters' => $filterOptions,
        ];
    }
}
