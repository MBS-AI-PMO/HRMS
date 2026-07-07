<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\company;
use App\Models\Employee;
use App\Models\EmployeeActivityLog;
use App\Models\GeneralSetting;
use App\Models\Holiday;
use App\Models\IpSetting;
use App\Imports\AttendancesImport;
use App\Imports\AttendancesImportDevice;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

use App\Http\traits\MonthlyWorkedHours;
use App\Services\AttendanceOvertimeService;
use App\Scopes\AuthCompanyScope;
use App\Support\AttendanceLocationCapture;
use App\Support\CompanyScope;
use App\Support\ManagedEmployeeScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\location;

class AttendanceController extends Controller {

	use MonthlyWorkedHours;

	public $date_attendance = [];
	public $date_range = [];
	public $work_days = 0;
	protected $monthlyAbbreviationCounts = [];
	protected $currentEmployeeHolidays;
	protected $employeeHolidaysCache = [];
	protected $date_attendance_iso = [];
	protected static $employeesHaveCnicColumn;

	protected function monthlyAbbreviationKeys(): array
	{
		return ['P', 'A', 'CL', 'SL', 'ML', 'SPL', 'WFH', 'LT', 'HD', 'EL', 'OFF'];
	}

	protected function resetMonthlyAbbreviationCounts(): void
	{
		$this->monthlyAbbreviationCounts = array_fill_keys($this->monthlyAbbreviationKeys(), 0);
	}

	protected function incrementMonthlyAbbreviationCount(string $code): void
	{
		if (array_key_exists($code, $this->monthlyAbbreviationCounts)) {
			$this->monthlyAbbreviationCounts[$code]++;
		}
	}

	protected function monthlyAbbreviationCount(string $code): int
	{
		return (int) ($this->monthlyAbbreviationCounts[$code] ?? 0);
	}

    protected function getLateGraceMinutes(): int
    {
        $lateGraceMinutes = GeneralSetting::value('late_grace_minutes');

        return is_numeric($lateGraceMinutes) ? max(0, (int) $lateGraceMinutes) : 0;
    }

    protected function getLateCutoffTime(DateTime $shiftIn): DateTime
    {
        $lateCutoffTime = clone $shiftIn;
        $lateGraceMinutes = $this->getLateGraceMinutes();

        if ($lateGraceMinutes > 0) {
            $lateCutoffTime->modify("+{$lateGraceMinutes} minutes");
        }

        return $lateCutoffTime;
    }

    protected function logEmployeeActivity(int $employeeId, string $action, string $description, array $meta = []): void
    {
        EmployeeActivityLog::create([
            'employee_id' => $employeeId,
            'performed_by' => optional(auth()->user())->id,
            'action' => $action,
            'description' => $description,
            'meta' => empty($meta) ? null : $meta,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Portal date filters use env Date_Format (e.g. d-m-Y); DB stores Y-m-d.
     */
    protected function parseAttendanceFilterDate($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $value = trim((string) $value);
        $fmt = env('Date_Format', 'd-m-Y');

        try {
            return Carbon::createFromFormat($fmt, $value)->format('Y-m-d');
        } catch (Exception $e) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (Exception $e2) {
                return null;
            }
        }
    }

    protected function resolveAttendanceListDate(?string $filter): string
    {
        return $this->parseAttendanceFilterDate($filter) ?? now()->format('Y-m-d');
    }

    protected function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    protected function validateLocationBasedAttendance(Request $request, Employee $employee): ?string
    {
        if (($employee->attendance_type ?? '') !== 'location_based') {
            return null;
        }

        $location = $employee->location;
        if (! $location) {
            return __('No location is assigned to your profile.');
        }

        if ($location->latitude === null || $location->longitude === null || $location->max_radius === null) {
            return __('Assigned location geofence is not configured.');
        }

        $userLat = is_numeric($request->latitude) ? (float) $request->latitude : null;
        $userLng = is_numeric($request->longitude) ? (float) $request->longitude : null;
        if ($userLat === null || $userLng === null) {
            return __('Current GPS location is required for location based attendance.');
        }

        $distance = $this->distanceInMeters(
            (float) $location->latitude,
            (float) $location->longitude,
            $userLat,
            $userLng
        );

        if ($distance > (float) $location->max_radius) {
            return __('You are currently outside the allowed office area. Please move closer to the office and try again.');
        }

        return null;
    }

    protected function validateIpBasedAttendance(Employee $employee, Request $request): ?string
    {
        if (($employee->attendance_type ?? '') !== 'ip_based') {
            return null;
        }

        $ipSettings = IpSetting::all();

        if ($ipSettings->isEmpty()) {
            return __('Office IP is not configured.');
        }

        $clientIp = (string) $request->ip();
        $clientPrefix = implode('.', array_slice(explode('.', $clientIp), 0, 3));
        $allowed = $ipSettings->contains(function ($ipRow) use ($clientPrefix) {
            $configuredPrefix = implode('.', array_slice(explode('.', (string) $ipRow->ip_address), 0, 3));

            return $configuredPrefix !== '' && $configuredPrefix === $clientPrefix;
        });

        if (! $allowed) {
            return __('Please connect to office internet for attendance.');
        }

        return null;
    }

    protected function mergeClockInLocation(array &$data, Request $request): void
    {
        $data = array_merge($data, AttendanceLocationCapture::clockInFields($request));
    }

    protected function mergeClockOutLocation(array &$data, Request $request): void
    {
        $data = array_merge($data, AttendanceLocationCapture::clockOutFields($request));
    }

    protected function handleOffDayEmployeeAttendance(Request $request, $id, Employee $employee, ?Attendance $employee_attendance_last)
    {
        $current_day = now()->format(env('Date_Format'));
        $current_time = new DateTime(now()->format('H:i'));

        if (! $employee_attendance_last || (int) $employee_attendance_last->clock_in_out === 0) {
            if (! AttendanceOvertimeService::canOvertimeOnOffDay($employee_attendance_last)) {
                return redirect()->back()->withErrors([__('Already clocked in. Please clock out first.')]);
            }

            $data = AttendanceOvertimeService::applyOvertimeClockInDefaults([
                'employee_id' => $id,
                'attendance_date' => $current_day,
                'attendance_status' => 'present',
                'clock_in_out' => 1,
                'clock_in_ip' => $request->ip(),
            ], $current_time);
            $this->mergeClockInLocation($data, $request);

            $attendance = Attendance::create($data);
            $this->logEmployeeActivity($id, 'attendance.overtime_clock_in', 'Employee started overtime on off day.', array_merge([
                'attendance_id' => $attendance->id,
                'attendance_date' => $data['attendance_date'] ?? null,
                'clock_in' => $data['clock_in'] ?? null,
            ], AttendanceLocationCapture::metaFromRequest($request)));
            $this->setSuccessMessage(__('Overtime Clocked In Successfully'));

            return redirect()->back();
        }

        if (AttendanceOvertimeService::isActiveOvertimeSession($employee_attendance_last)) {
            $data = AttendanceOvertimeService::buildOvertimeClockOutUpdate(
                $employee_attendance_last,
                $current_time,
                $request->ip()
            );
            $this->mergeClockOutLocation($data, $request);

            $attendance = Attendance::findOrFail($employee_attendance_last->id);
            $attendance->update($data);
            $this->logEmployeeActivity($id, 'attendance.overtime_clock_out', 'Employee ended overtime on off day.', array_merge([
                'attendance_id' => $attendance->id,
                'attendance_date' => $attendance->attendance_date,
                'clock_out' => $data['clock_out'] ?? null,
                'overtime' => $data['overtime'] ?? null,
            ], AttendanceLocationCapture::metaFromRequest($request)));
            $this->setSuccessMessage(__('Overtime Clocked Out Successfully'));

            return redirect()->back();
        }

        return redirect()->back()->withErrors([__('Regular attendance is not available on off days. Please use overtime clock-in.')]);
    }

    protected function attendanceEmployeeBaseQuery()
    {
        return Employee::withoutGlobalScope(AuthCompanyScope::class);
    }

    protected function parseAttendanceFilterMonthYear(?string $monthYear): string
    {
        if (! $monthYear) {
            return date('F Y');
        }

        $monthYear = trim($monthYear);

        if (preg_match('/^(\d{1,2})\s+(\d{4})$/', $monthYear, $matches)) {
            return Carbon::createFromDate((int) $matches[2], (int) $matches[1], 1)->format('F Y');
        }

        return $monthYear;
    }

    /**
     * Apply company / client / location / department / employee filters to an employee query.
     */
    protected function applyAttendanceEmployeeFilters($query, Request $request, string $prefix = ''): void
    {
        $companyKey = $prefix ? $prefix.'company' : 'company_id';
        $clientKey = $prefix ? $prefix.'client' : 'client_id';
        $locationKey = $prefix ? $prefix.'location' : 'location_id';
        $employeeKey = $prefix ? $prefix.'employee' : 'employee_id';

        if ($request->filled($employeeKey)) {
            $query->where('id', (int) $request->input($employeeKey));

            return;
        }

        if ($request->filled($clientKey)) {
            $query->where('client_id', (int) $request->input($clientKey));
        } elseif ($request->filled($companyKey)) {
            $companyId = (int) $request->input($companyKey);
            $linkedClientIds = CompanyScope::clientIdsForCompany($companyId);

            $query->where(function ($q) use ($companyId, $linkedClientIds) {
                $q->where('company_id', $companyId);

                if ($linkedClientIds !== []) {
                    $q->orWhereIn('client_id', $linkedClientIds);
                }
            });
        }

        if ($request->filled($locationKey)) {
            $query->where('location_id', (int) $request->input($locationKey));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', (int) $request->input('department_id'));
        }
    }

    protected function canUseAttendanceFilters($logged_user): bool
    {
        return (int) $logged_user->role_users_id === 1
            || ManagedEmployeeScope::canAccessScopedEmployeeList((int) $logged_user->id, (int) $logged_user->role_users_id);
    }

    protected function employeeCompanyLabel(Employee $employee): string
    {
        if ($employee->company?->company_name) {
            return (string) $employee->company->company_name;
        }

        if ($employee->client?->company_name) {
            return (string) $employee->client->company_name;
        }

        return '—';
    }

    protected function holidaysForEmployee(Employee $employee)
    {
        $cacheKey = (int) $employee->id;

        if (array_key_exists($cacheKey, $this->employeeHolidaysCache)) {
            return $this->employeeHolidaysCache[$cacheKey];
        }

        if ($employee->company?->relationLoaded('companyHolidays')) {
            return $this->employeeHolidaysCache[$cacheKey] = $employee->company->companyHolidays;
        }

        if ($employee->company_id) {
            return $this->employeeHolidaysCache[$cacheKey] = Holiday::query()
                ->where('company_id', $employee->company_id)
                ->get();
        }

        if ($employee->client_id) {
            $companyId = CompanyScope::resolveCompanyIdForClient((int) $employee->client_id);

            if ($companyId) {
                return $this->employeeHolidaysCache[$cacheKey] = Holiday::query()
                    ->where('company_id', $companyId)
                    ->get();
            }
        }

        return $this->employeeHolidaysCache[$cacheKey] = collect();
    }

    protected function monthlyEmployeeSelectColumns(): array
    {
        $columns = [
            'id', 'company_id', 'client_id', 'first_name', 'last_name',
            'department_id', 'designation_id', 'office_shift_id',
        ];

        if ($this->employeesHaveCnicColumn()) {
            $columns[] = 'cnic';
        }

        return $columns;
    }

    protected function employeesHaveCnicColumn(): bool
    {
        if (static::$employeesHaveCnicColumn === null) {
            static::$employeesHaveCnicColumn = Schema::hasColumn('employees', 'cnic');
        }

        return static::$employeesHaveCnicColumn;
    }

    protected function monthlyLeaveAbbreviation($leaveItem): string
    {
        $leaveType = strtolower(trim((string) optional($leaveItem->LeaveType)->leave_type));

        if (str_contains($leaveType, 'wfh') || str_contains($leaveType, 'work from home')) {
            return 'WFH';
        }

        if ((float) ($leaveItem->total_days ?? 0) > 0 && (float) $leaveItem->total_days < 1) {
            return 'HD';
        }

        if (str_contains($leaveType, 'half')) {
            return 'HD';
        }

        if (str_contains($leaveType, 'casual') || $leaveType === 'cl') {
            return 'CL';
        }

        if (str_contains($leaveType, 'sick') || $leaveType === 'sl') {
            return 'SL';
        }

        if (str_contains($leaveType, 'maternity') || $leaveType === 'ml') {
            return 'ML';
        }

        if (str_contains($leaveType, 'special') || $leaveType === 'spl') {
            return 'SPL';
        }

        return 'CL';
    }

    protected function monthlyPresentAbbreviation($attendance): string
    {
        $timeLate = trim((string) ($attendance->time_late ?? '00:00'));
        $earlyLeaving = trim((string) ($attendance->early_leaving ?? '00:00'));

        $isLate = $timeLate !== '' && ! in_array($timeLate, ['00:00', '00:00:00', '---'], true);
        $isEarly = $earlyLeaving !== '' && ! in_array($earlyLeaving, ['00:00', '00:00:00', '---'], true);

        if ($isLate) {
            return 'LT';
        }

        if ($isEarly) {
            return 'EL';
        }

        return 'P';
    }

    protected function countMonthlyWorkedDay(string $code): void
    {
        if (in_array($code, ['P', 'LT', 'EL', 'WFH'], true)) {
            $this->work_days++;

            return;
        }

        if ($code === 'HD') {
            $this->work_days += 0.5;
        }
    }

    protected function employeeAttendanceRelations(string $selectedDate): array
    {
        return [
            'officeShift',
            'company:id,company_name',
            'client:id,company_name',
            'employeeAttendance' => function ($query) use ($selectedDate) {
                $query->whereDate('attendance_date', $selectedDate);
            },
            'employeeLeave' => function ($query) use ($selectedDate) {
                $query->where('start_date', '<=', $selectedDate)
                    ->where('end_date', '>=', $selectedDate);
            },
        ];
    }

	public function index(Request $request)
	{
		$logged_user = auth()->user();

        if (! $logged_user->can('daily-attendances')) {
            return abort(403, __('You are not authorized'));
        }

		$canManageScopedAttendance = ManagedEmployeeScope::canAccessScopedEmployeeList((int) $logged_user->id, (int) $logged_user->role_users_id);
		$canUseAttendanceFilters = $this->canUseAttendanceFilters($logged_user);
		$managedEmployeeIds = $canManageScopedAttendance
			? ManagedEmployeeScope::managedEmployeeIds((int) $logged_user->id)
			: [];

        $isLocationHead = location::userIsLocationHead((int) $logged_user->id);
        $companies = $isLocationHead
            ? CompanyScope::companiesForLocationHead((int) $logged_user->id)
            : CompanyScope::companiesForSelect();
        if ($companies->isEmpty()) {
            $companies = company::all('id', 'company_name');
        }

			$selected_date = $this->resolveAttendanceListDate($request->filter_month_year);

			$day = strtolower(Carbon::parse($selected_date)->format('l')).'_in';


			if (request()->ajax())
			{
                if (! $canUseAttendanceFilters) {
                    $request->merge(['employee_id' => (int) $logged_user->id]);
                }

				$employeeQuery = $this->attendanceEmployeeBaseQuery()
                    ->with($this->employeeAttendanceRelations($selected_date))
					->select('id', 'company_id', 'client_id', 'first_name', 'last_name', 'office_shift_id')
					->where('joining_date', '<=', $selected_date)
                    ->where('is_active', 1)
                    ->where(function ($query) use ($selected_date) {
						$query->whereNull('exit_date')
							->orWhere('exit_date', '>=', $selected_date)
							->orWhere('exit_date', '0000-00-00');
					});

				if ((int) $logged_user->role_users_id === 1) {
                    if ($request->filled('company_id') || $request->filled('client_id')
                        || $request->filled('location_id') || $request->filled('employee_id')) {
                        $this->applyAttendanceEmployeeFilters($employeeQuery, $request);
                    }
				} elseif ($canManageScopedAttendance) {
					$employeeQuery->whereIn('id', $managedEmployeeIds);
                    if ($request->filled('company_id') || $request->filled('client_id')
                        || $request->filled('location_id') || $request->filled('employee_id')) {
                        $this->applyAttendanceEmployeeFilters($employeeQuery, $request);
                    }
				} else {
					$employeeQuery->where('id', (int) $logged_user->id);
				}

				$employee = $employeeQuery->get();



				$holidays = Holiday::select('id', 'company_id', 'start_date', 'end_date', 'is_publish')
					->where('start_date', '<=', $selected_date)
					->where('end_date', '>=', $selected_date)
					->where('is_publish', '=', 1)->first();


				return datatables()->of($employee)
					->setRowId(function ($employee)
					{
						return $employee->id;
					})
					->addColumn('employee_name', function ($employee)
					{
						return $employee->full_name;
					})
					->addColumn('company', function ($employee)
					{
						return $this->employeeCompanyLabel($employee);
					})
					->addColumn('attendance_date', function ($employee) use ($selected_date)
					{
						//if there is no employee attendance
						if ($employee->employeeAttendance->isEmpty())
						{
							return Carbon::parse($selected_date)->format(env('Date_Format'));
						} else
						{
							//if there are employee attendance,get the first record
							$attendance_row = $employee->employeeAttendance->first();

							return $attendance_row->attendance_date;
						}
					})
					->addColumn('attendance_status', function ($employee) use ($holidays, $day)
					{
						//if there are employee attendance,get the first record
						if ($employee->employeeAttendance->isEmpty())
						{
							$shiftDay = optional($employee->officeShift)->$day ?? null;
							if (is_null($shiftDay) || $shiftDay === '')
							{
								return __('Off Day');
							}

							if ($holidays)
							{
								if ($employee->company_id && (int) $employee->company_id === (int) $holidays->company_id)
								{
									return trans('file.Holiday');
								}
							}


							if ($employee->employeeLeave->isEmpty())
							{
								return trans('file.Absent');
							}

							return __('On leave');

						} else
						{
							$attendance_row = $employee->employeeAttendance->first();

							return $attendance_row->attendance_status;
						}
					})
					->addColumn('clock_in', function ($employee)
					{
						if ($employee->employeeAttendance->isEmpty())
						{
							return '---';
						} else
						{
							$attendance_row = $employee->employeeAttendance->first();

							return $attendance_row->clock_in;
						}
					})
					->addColumn('clock_out', function ($employee)
					{
						if ($employee->employeeAttendance->isEmpty())
						{
							return '---';
						} else
						{
							$attendance_row = $employee->employeeAttendance->last();

							return $attendance_row->clock_out;
						}
					})
					->addColumn('time_late', function ($employee)
					{
						if ($employee->employeeAttendance->isEmpty())
						{
							return '---';
						} else
						{
							$attendance_row = $employee->employeeAttendance->first();

							return $attendance_row->time_late;
						}
					})
					->addColumn('early_leaving', function ($employee)
					{
						if ($employee->employeeAttendance->isEmpty())
						{
							return '---';
						} else
						{
							$attendance_row = $employee->employeeAttendance->last();

							return $attendance_row->early_leaving;
						}
					})
					->addColumn('overtime', function ($employee)
					{
						if ($employee->employeeAttendance->isEmpty())
						{
							return '---';
						}
                        else
						{
							$total = 0;
							foreach ($employee->employeeAttendance as $attendance_row)
							{
								sscanf($attendance_row->overtime, '%d:%d', $hour, $min);
								$total += $hour * 60 + $min;
							}
							if ($h = floor($total / 60))
							{
								$total %= 60;
							}

							return sprintf('%02d:%02d', $h, $total);
						}
					})
					->addColumn('total_work', function ($employee)
					{
						if ($employee->employeeAttendance->isEmpty())
						{
							return '---';
						}
                        else
						{
							$total = 0;
							foreach ($employee->employeeAttendance as $attendance_row)
							{
								sscanf($attendance_row->total_work, '%d:%d', $hour, $min);
								$total += $hour * 60 + $min;
							}
							if ($h = floor($total / 60))
							{
								$total %= 60;
							}
							return sprintf('%02d:%02d', $h, $total);
						}
					})
					->addColumn('total_rest', function ($employee)
					{
						if ($employee->employeeAttendance->isEmpty())
						{
							return '---';
						}
                        else
						{
							$total = 0;
							foreach ($employee->employeeAttendance as $attendance_row)
							{
								//formatting in hour:min and separating them
								sscanf($attendance_row->total_rest, '%d:%d', $hour, $min);
								//converting in minute
								$total += $hour * 60 + $min;
							}
							// if minute is greater than hour then $h= hour
							if ($h = floor($total / 60))
							{
								$total %= 60;
							}
							//returning back to hour:minute format
							return sprintf('%02d:%02d', $h, $total);
						}
					})
					->rawColumns(['action'])
					->make(true);
			}

			return view('timesheet.attendance.attendance', compact('companies', 'canUseAttendanceFilters'));
		// }

		return response()->json(['success' => __('You are not authorized')]);
	}


	public function employeeAttendance(Request $request, $id)
	{

		$data = [];
        $employee = Employee::with('location')->findOrFail($id);
        if ((int) optional(auth()->user())->id !== (int) $id && ! auth()->user()->can('view-attendance')) {
            return redirect()->back()->withErrors([__('You are not authorized')]);
        }

        $gpsError = AttendanceLocationCapture::gpsValidationError($request);
        if ($gpsError) {
            return redirect()->back()->withErrors([$gpsError]);
        }

        $ipError = $this->validateIpBasedAttendance($employee, $request);
        if ($ipError) {
            return redirect()->back()->withErrors([$ipError]);
        }

        $locationError = $this->validateLocationBasedAttendance($request, $employee);
        if ($locationError) {
            return redirect()->back()->withErrors([$locationError]);
        }

		$current_day = now()->format(env('Date_Format'));

		$employee_attendance_last = Attendance::where('attendance_date', now()->format('Y-m-d'))
				->where('employee_id', $id)->orderBy('id', 'desc')->first() ?? null;

		$shiftInRaw = trim((string) $request->office_shift_in);
		$shiftOutRaw = trim((string) $request->office_shift_out);

		if (AttendanceOvertimeService::isOffDay($shiftInRaw ?: null, $shiftOutRaw ?: null)) {
			return $this->handleOffDayEmployeeAttendance($request, $id, $employee, $employee_attendance_last);
		}

		try
		{
			$shift_in = new DateTime($request->office_shift_in);
			$shift_out = new DateTime($request->office_shift_out);
			$current_time = new DateTime(now()->format('H:i'));

		} catch (Exception $e)
		{
			return redirect()->back()->withErrors([__('Invalid shift timing.')]);
		}

		$data['employee_id'] = $id;
		$data['attendance_date'] = $current_day;

		$clockInAsOvertime = AttendanceOvertimeService::shouldClockInAsOvertime(
			$shift_out,
			$current_time,
			$employee_attendance_last
		);

		if (! $employee_attendance_last || (int) $employee_attendance_last->clock_in_out === 0) {
			if ($clockInAsOvertime) {
				$data = AttendanceOvertimeService::applyOvertimeClockInDefaults($data, $current_time);
				$data['attendance_status'] = 'present';
				$data['clock_in_out'] = 1;
				$data['clock_in_ip'] = $request->ip();
				$this->mergeClockInLocation($data, $request);

				$attendance = Attendance::create($data);
				$this->logEmployeeActivity($id, 'attendance.overtime_clock_in', 'Employee started overtime.', array_merge([
					'attendance_id' => $attendance->id,
					'attendance_date' => $data['attendance_date'] ?? null,
					'clock_in' => $data['clock_in'] ?? null,
				], AttendanceLocationCapture::metaFromRequest($request)));
				$this->setSuccessMessage(__('Overtime Clocked In Successfully'));

				return redirect()->back();
			}

			if (! $employee_attendance_last) {
				$late_cutoff_time = $this->getLateCutoffTime($shift_in);

				if ($current_time > $late_cutoff_time) {
					$data['clock_in'] = $current_time->format('H:i');
					$data['time_late'] = $late_cutoff_time->diff(new DateTime($data['clock_in']))->format('%H:%I');
				} else {
					if (env('ENABLE_EARLY_CLOCKIN') != null) {
						$data['clock_in'] = $current_time->format('H:i');
					} else {
						$data['clock_in'] = $shift_in->format('H:i');
					}
				}

				$data['attendance_status'] = 'present';
				$data['clock_in_out'] = 1;
				$data['clock_in_ip'] = $request->ip();
				$data['is_overtime'] = 0;
				$this->mergeClockInLocation($data, $request);

				$attendance = Attendance::create($data);
				$this->logEmployeeActivity($id, 'attendance.clock_in', 'Employee clocked in.', array_merge([
					'attendance_id' => $attendance->id,
					'attendance_date' => $data['attendance_date'] ?? null,
					'clock_in' => $data['clock_in'] ?? null,
					'time_late' => $data['time_late'] ?? '00:00',
				], AttendanceLocationCapture::metaFromRequest($request)));
				$this->setSuccessMessage(__('Clocked In Successfully'));

				return redirect()->back();
			}

			$data['clock_in'] = $current_time->format('H:i');
			$employee_last_clock_out = new DateTime($employee_attendance_last->clock_out);
			$data['total_rest'] = $employee_last_clock_out->diff(new DateTime($data['clock_in']))->format('%H:%I');
			$data['total_work'] = $employee_attendance_last->total_work;
			$data['overtime'] = $employee_attendance_last->overtime;
			$data['clock_in_out'] = 1;
			$data['clock_in_ip'] = $request->ip();
			$data['is_overtime'] = 0;
			$this->mergeClockInLocation($data, $request);

			Attendance::whereId($employee_attendance_last->id)->update(['total_work' => '00:00', 'overtime' => '00:00']);
			$attendance = Attendance::create($data);
			$this->logEmployeeActivity($id, 'attendance.re_clock_in', 'Employee clocked in again after break.', array_merge([
				'attendance_id' => $attendance->id,
				'attendance_date' => $data['attendance_date'] ?? null,
				'clock_in' => $data['clock_in'] ?? null,
				'total_rest' => $data['total_rest'] ?? null,
			], AttendanceLocationCapture::metaFromRequest($request)));
			$this->setSuccessMessage(__('Clocked In Successfully'));

			return redirect()->back();
		}

		if ((int) $employee_attendance_last->clock_in_out === 1) {
			if (AttendanceOvertimeService::isActiveOvertimeSession($employee_attendance_last)) {
				$data = AttendanceOvertimeService::buildOvertimeClockOutUpdate(
					$employee_attendance_last,
					$current_time,
					$request->ip()
				);
				$this->mergeClockOutLocation($data, $request);

				$attendance = Attendance::findOrFail($employee_attendance_last->id);
				$attendance->update($data);
				$this->logEmployeeActivity($id, 'attendance.overtime_clock_out', 'Employee ended overtime.', array_merge([
					'attendance_id' => $attendance->id,
					'attendance_date' => $attendance->attendance_date,
					'clock_out' => $data['clock_out'] ?? null,
					'overtime' => $data['overtime'] ?? null,
				], AttendanceLocationCapture::metaFromRequest($request)));
				$this->setSuccessMessage(__('Overtime Clocked Out Successfully'));

				return redirect()->back();
			}

			if ($current_time > $shift_in || env('ENABLE_EARLY_CLOCKIN') != null) {
				$employee_last_clock_in = new DateTime($employee_attendance_last->clock_in);
				$data['clock_out'] = $current_time->format('H:i');

				if ($current_time < $shift_out) {
					$data['early_leaving'] = $shift_out->diff(new DateTime($data['clock_out']))->format('%H:%I');
				}

				$prev_work = new DateTime($employee_attendance_last->total_work);
				$total_work = $prev_work->add($employee_last_clock_in->diff(new DateTime($data['clock_out'])));
				$data['total_work'] = $total_work->format('H:i');

				$duty_time = new DateTime($shift_in->diff($shift_out)->format('%H:%I'));
				if ($total_work > $duty_time) {
					$data['overtime'] = $total_work->diff($duty_time)->format('%H:%I');
				}

				$data['clock_out_ip'] = $request->ip();
				$data['clock_in_out'] = 0;
				$this->mergeClockOutLocation($data, $request);

				$attendance = Attendance::findOrFail($employee_attendance_last->id);
				$attendance->update($data);
				$this->logEmployeeActivity($id, 'attendance.clock_out', 'Employee clocked out.', array_merge([
					'attendance_id' => $attendance->id,
					'attendance_date' => $attendance->attendance_date,
					'clock_out' => $data['clock_out'] ?? null,
					'total_work' => $data['total_work'] ?? null,
					'overtime' => $data['overtime'] ?? null,
				], AttendanceLocationCapture::metaFromRequest($request)));
			} else {
				Attendance::whereId($employee_attendance_last->id)->delete();
			}

			$this->setSuccessMessage(__('Clocked Out Successfully'));

			return redirect()->back();
		}

		return response()->json(trans('file.Success'));
	}


    public function test($request, $companies, $start_date, $end_date)
    {
        $request->employee_id   = 9;
        $request->company_id    = 1;
        $request->department_id = 1;


        $employee = Employee::with(['officeShift', 'employeeAttendance' => function ($query) use ($start_date, $end_date)
        {
            $query->whereBetween('attendance_date', [$start_date, $end_date]);
        },
            'employeeLeave',
            'company:id,company_name',
            'company.companyHolidays'
        ])
        ->select('id', 'company_id', 'first_name', 'last_name', 'office_shift_id', 'joining_date')
        ->where('is_active', '=', 1);

        if ($request->employee_id) {
            $employee = $employee->where('id', '=', $request->employee_id)->get();
        }
        elseif ($request->department_id) {
            $employee = $employee->where('department_id', '=', $request->department_id)->get();
        }
        elseif ($request->company_id) {
            $employee = $employee->where('company_id', '=', $request->company_id)->get();
        }

        $begin = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day');
        $interval = DateInterval::createFromDateString('1 day');
        $period   = new DatePeriod($begin, $interval, $end);
        $date_range = [];
        foreach ($period as $dt) {
            $date_range[] = $dt->format(env('Date_Format'));
        }
        $emp_attendance_date_range = [];


        foreach ($employee as $key1 => $emp) {
            $all_attendances_array = $emp->employeeAttendance->groupBy('attendance_date')->toArray();
            $leaves = $emp->employeeLeave;
            $shift = $emp->officeShift->toArray();
            $holidays = $emp->company->companyHolidays;
            $joining_date = Carbon::parse($emp->joining_date)->format(env('Date_Format'));
            foreach ($date_range as $key2 => $dt_r) {
                $emp_attendance_date_range[$key1*count($date_range)+$key2]['id'] = $emp->id;
                $emp_attendance_date_range[$key1*count($date_range)+$key2]['employee_name'] = ($key2==0) ? '<strong>'.$emp->full_name.'</strong>' : $emp->full_name;
                $emp_attendance_date_range[$key1*count($date_range)+$key2]['company'] = $emp->company->company_name;
                $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_date'] = Carbon::parse($dt_r)->format(env('Date_Format'));

                //attendance status
                $day = strtolower(Carbon::parse($dt_r)->format('l')) . '_in';
                if (strtotime($dt_r) < strtotime($joining_date))
                {
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = __('Not Join');
                }
                elseif (empty($shift[$day]))
                {
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = __('Off Day');
                }
                elseif (array_key_exists($dt_r, $all_attendances_array))
                {
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = trans('file.present');
                }
                else
                {
                    foreach ($leaves as $leave)
                    {
                        // Test Start
                        // $start_date = Carbon::parse($leave->start_date);
                        // $end_date   = Carbon::parse($leave->end_date);
                        // $dateRange  = Carbon::parse($dt_r);

                        $leaveDateTimesStart = strtotime($leave->start_date);
                        $leaveDateTimesEnd   = strtotime($leave->end_date);
                        $dateRange           = strtotime($dt_r);

						return $leaveDateTimesStart;

                        if ($leaveDateTimesStart <= $dateRange){
                            return $dt_r;
                        }
                        // return gettype($start_date);

                        // if ($start_date->lte($dateRange) && $end_date->gte($dateRange)) {
                        //     // $date1 is less than or equal to $date2 || // $date1 is greater than or equal to $date2
                        //     return $dateRange;
                        // }

                        return $dt_r;

                        // Test End

                        if ($leave->start_date <= $dt_r && $leave->end_date >= $dt_r)
                        {
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = __('On Leave');
                        }
                    }
                    foreach ($holidays as $holiday)
                    {
                        if ($holiday->start_date <= $dt_r && $holiday->end_date >= $dt_r)
                        {
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = __('On Holiday');
                        }
                    }
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = trans('Absent');
                }

                //attendance status

                //clock in
                if (array_key_exists($dt_r, $all_attendances_array))
                {
                    $first = current($all_attendances_array[$dt_r])['clock_in'];
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['clock_in'] = $first;
                }
                else
                {
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['clock_in'] = '---';
                }
                //clock in

                //clock out
                if (array_key_exists($dt_r, $all_attendances_array))
                {
                    $last = end($all_attendances_array[$dt_r])['clock_out'];
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['clock_out'] = $last;
                }
                else
                {
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['clock_out'] = '---';
                }
                //clock out

                //time late
                if (array_key_exists($dt_r, $all_attendances_array))
                {
                    $first = current($all_attendances_array[$dt_r])['time_late'];
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['time_late'] = $first;
                } else
                {
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['time_late'] = '---';
                }
                //time late

                //early_leaving
                if (array_key_exists($dt_r, $all_attendances_array))
                {
                    $last = end($all_attendances_array[$dt_r])['early_leaving'];
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['early_leaving'] = $last;
                } else
                {
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['early_leaving'] = '---';
                }
                //early_leaving

                //overtime
                if (array_key_exists($dt_r, $all_attendances_array))
                {
                    $total = 0;
                    foreach ($all_attendances_array[$dt_r] as $all_attendance_item)
                    {
                        sscanf($all_attendance_item['overtime'], '%d:%d', $hour, $min);
                        $total += $hour * 60 + $min;
                    }
                    if ($h = floor($total / 60))
                    {
                        $total %= 60;
                    }
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['overtime'] = sprintf('%02d:%02d', $h, $total);
                } else
                {
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['overtime'] = '---';
                }
                //overtime

                //total_work
                if (array_key_exists($dt_r, $all_attendances_array))
                {
                    $total = 0;
                    foreach ($all_attendances_array[$dt_r] as $all_attendance_item)
                    {
                        sscanf($all_attendance_item['total_work'], '%d:%d', $hour, $min);
                        $total += $hour * 60 + $min;
                    }
                    if ($h = floor($total / 60))
                    {
                        $total %= 60;
                    }
                    $sum_total = 0 + $total;
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['total_work'] = sprintf('%02d:%02d', $h, $total);
                }
                else
                {
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['total_work'] = '---';
                }
                //total_work

                //total_rest
                if (array_key_exists($dt_r, $all_attendances_array))
                {
                    $total = 0;
                    foreach ($all_attendances_array[$dt_r] as $all_attendance_item)
                    {
                        //formatting in hour:min and separating them
                        sscanf($all_attendance_item['total_rest'], '%d:%d', $hour, $min);
                        //converting in minute
                        $total += $hour * 60 + $min;
                    }
                    // if minute is greater than hour then $h= hour
                    if ($h = floor($total / 60))
                    {
                        //$total = minute (after excluding hour)
                        $total %= 60;
                    }
                    //returning back to hour:minute format
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['total_rest'] = sprintf('%02d:%02d', $h, $total);
                } else
                {
                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['total_rest'] = '---';
                }
                //total_rest
            }
        }
        return 'END';
    }


	// public function dateWiseAttendance(Request $request)
	// {
	// 	$logged_user = auth()->user();

    //     $companies = Company::all('id', 'company_name');
    //     $start_date = Carbon::parse($request->filter_start_date)->format('Y-m-d') ?? '';
    //     $end_date = Carbon::parse($request->filter_end_date)->format('Y-m-d') ?? '';

    //     if (request()->ajax())
    //     {
    //         if (!$request->company_id && !$request->department_id && !$request->employee_id) {
    //             $emp_attendance_date_range = [];
    //         }
    //         else
    //         {
    //             $employee = Employee::with(['officeShift', 'employeeAttendance' => function ($query) use ($start_date, $end_date)
    //             {
    //                 $query->whereBetween('attendance_date', [$start_date, $end_date]);
    //             },
    //                 'employeeLeave',
    //                 'company:id,company_name',
    //                 'company.companyHolidays'
    //             ])
    //             ->select('id', 'company_id', 'first_name', 'last_name', 'office_shift_id', 'joining_date')
    //             ->where('is_active', '=', 1);

    //             if ($request->employee_id) {
    //                 $employee = $employee->where('id', '=', $request->employee_id)->get();
    //             }
    //             elseif ($request->department_id) {
    //                 $employee = $employee->where('department_id', '=', $request->department_id)->get();
    //             }
    //             elseif ($request->company_id) {
    //                 $employee = $employee->where('company_id', '=', $request->company_id)->get();
    //             }

    //             $begin = new DateTime($start_date);
    //             $end = new DateTime($end_date);
    //             $end->modify('+1 day');
    //             $interval = DateInterval::createFromDateString('1 day');
    //             $period   = new DatePeriod($begin, $interval, $end);
    //             $date_range = [];
    //             foreach ($period as $dt) {
    //                 $date_range[] = $dt->format(env('Date_Format'));
    //             }
    //             $emp_attendance_date_range = [];

    //             foreach ($employee as $key1 => $emp) {
    //                 $all_attendances_array = $emp->employeeAttendance->groupBy('attendance_date')->toArray();
    //                 $leaves = $emp->employeeLeave;
    //                 $shift = $emp->officeShift->toArray();
    //                 $holidays = $emp->company->companyHolidays;
    //                 $joining_date = Carbon::parse($emp->joining_date)->format(env('Date_Format'));
    //                 foreach ($date_range as $key2 => $dt_r) {
    //                     $emp_attendance_date_range[$key1*count($date_range)+$key2]['id'] = $emp->id;
    //                     $emp_attendance_date_range[$key1*count($date_range)+$key2]['employee_name'] = ($key2==0) ? '<strong>'.$emp->full_name.'</strong>' : $emp->full_name;
    //                     $emp_attendance_date_range[$key1*count($date_range)+$key2]['company'] = $emp->company->company_name;
    //                     $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_date'] = Carbon::parse($dt_r)->format(env('Date_Format'));

    //                     //attendance status
    //                     $day = strtolower(Carbon::parse($dt_r)->format('l')) . '_in';
    //                     if (strtotime($dt_r) < strtotime($joining_date))
    //                     {
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = __('Not Join');
    //                     }
    //                     elseif (empty($shift[$day]))
    //                     {
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = __('Off Day');
    //                     }
    //                     elseif (array_key_exists($dt_r, $all_attendances_array))
    //                     {
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = trans('file.present');
    //                     }
    //                     else
    //                     {
    //                         foreach ($leaves as $leave)
    //                         {
    //                             if ($leave->start_date <= $dt_r && $leave->end_date >= $dt_r)
    //                             {
    //                                 $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = __('On Leave');
    //                             }
    //                         }
    //                         foreach ($holidays as $holiday)
    //                         {
    //                             if ($holiday->start_date <= $dt_r && $holiday->end_date >= $dt_r)
    //                             {
    //                                 $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = __('On Holiday');
    //                             }
    //                         }
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = trans('Absent');
    //                     }
    //                     //attendance status

    //                     //clock in
    //                     if (array_key_exists($dt_r, $all_attendances_array))
    //                     {
    //                         $first = current($all_attendances_array[$dt_r])['clock_in'];
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['clock_in'] = $first;
    //                     }
    //                     else
    //                     {
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['clock_in'] = '---';
    //                     }
    //                     //clock in

    //                     //clock out
    //                     if (array_key_exists($dt_r, $all_attendances_array))
    //                     {
    //                         $last = end($all_attendances_array[$dt_r])['clock_out'];
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['clock_out'] = $last;
    //                     }
    //                     else
    //                     {
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['clock_out'] = '---';
    //                     }
    //                     //clock out

    //                     //time late
    //                     if (array_key_exists($dt_r, $all_attendances_array))
    //                     {
    //                         $first = current($all_attendances_array[$dt_r])['time_late'];
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['time_late'] = $first;
    //                     } else
    //                     {
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['time_late'] = '---';
    //                     }
    //                     //time late

    //                     //early_leaving
    //                     if (array_key_exists($dt_r, $all_attendances_array))
    //                     {
    //                         $last = end($all_attendances_array[$dt_r])['early_leaving'];
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['early_leaving'] = $last;
    //                     } else
    //                     {
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['early_leaving'] = '---';
    //                     }
    //                     //early_leaving

    //                     //overtime
    //                     if (array_key_exists($dt_r, $all_attendances_array))
    //                     {
    //                         $total = 0;
    //                         foreach ($all_attendances_array[$dt_r] as $all_attendance_item)
    //                         {
    //                             sscanf($all_attendance_item['overtime'], '%d:%d', $hour, $min);
    //                             $total += $hour * 60 + $min;
    //                         }
    //                         if ($h = floor($total / 60))
    //                         {
    //                             $total %= 60;
    //                         }
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['overtime'] = sprintf('%02d:%02d', $h, $total);
    //                     } else
    //                     {
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['overtime'] = '---';
    //                     }
    //                     //overtime

    //                     //total_work
    //                     if (array_key_exists($dt_r, $all_attendances_array))
    //                     {
    //                         $total = 0;
    //                         foreach ($all_attendances_array[$dt_r] as $all_attendance_item)
    //                         {
    //                             sscanf($all_attendance_item['total_work'], '%d:%d', $hour, $min);
    //                             $total += $hour * 60 + $min;
    //                         }
    //                         if ($h = floor($total / 60))
    //                         {
    //                             $total %= 60;
    //                         }
    //                         $sum_total = 0 + $total;
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['total_work'] = sprintf('%02d:%02d', $h, $total);
    //                     }
    //                     else
    //                     {
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['total_work'] = '---';
    //                     }
    //                     //total_work

    //                     //total_rest
    //                     if (array_key_exists($dt_r, $all_attendances_array))
    //                     {
    //                         $total = 0;
    //                         foreach ($all_attendances_array[$dt_r] as $all_attendance_item)
    //                         {
    //                             //formatting in hour:min and separating them
    //                             sscanf($all_attendance_item['total_rest'], '%d:%d', $hour, $min);
    //                             //converting in minute
    //                             $total += $hour * 60 + $min;
    //                         }
    //                         // if minute is greater than hour then $h= hour
    //                         if ($h = floor($total / 60))
    //                         {
    //                             //$total = minute (after excluding hour)
    //                             $total %= 60;
    //                         }
    //                         //returning back to hour:minute format
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['total_rest'] = sprintf('%02d:%02d', $h, $total);
    //                     } else
    //                     {
    //                         $emp_attendance_date_range[$key1*count($date_range)+$key2]['total_rest'] = '---';
    //                     }
    //                     //total_rest
    //                 }
    //             }
    //         }

    //         return datatables()->of($emp_attendance_date_range)
    //             ->setRowId(function ($row)
    //             {
    //                 return $row['id'];
    //             })
    //             ->addColumn('employee_name', function ($row)
    //             {
    //                 return $row['employee_name'];
    //             })
    //             ->addColumn('company', function ($row)
    //             {
    //                 return $row['company'];
    //             })
    //             ->addColumn('attendance_date', function ($row)
    //             {
    //                 return $row['attendance_date'];
    //             })
    //             ->addColumn('attendance_status', function ($row)
    //             {
    //                 return $row['attendance_status'];
    //             })
    //             ->addColumn('clock_in', function ($row)
    //             {
    //                 return $row['clock_in'];
    //             })
    //             ->addColumn('clock_out', function ($row)
    //             {
    //                 return $row['clock_out'];
    //             })
    //             ->addColumn('time_late', function ($row)
    //             {
    //                 return $row['time_late'];
    //             })
    //             ->addColumn('early_leaving', function ($row)
    //             {
    //                 return $row['early_leaving'];
    //             })
    //             ->addColumn('overtime', function ($row)
    //             {
    //                 return $row['overtime'];
    //             })
    //             ->addColumn('total_work', function ($row)
    //             {
    //                 return $row['total_work'];
    //             })
    //             ->addColumn('total_rest', function ($row)
    //             {
    //                 return $row['total_rest'];
    //             })
    //             ->rawColumns(['action','employee_name'])
    //             ->make(true);
    //     }

    //     return view('timesheet.dateWiseAttendance.index', compact('companies'));
	// }

	public function dateWiseAttendance(Request $request)
	{
		$logged_user = auth()->user();

        if (! $logged_user->can('date-wise-attendances')) {
            return abort(403, __('You are not authorized'));
        }

        $isLocationHead = location::userIsLocationHead((int) $logged_user->id);
        $canManageScopedAttendance = ManagedEmployeeScope::canAccessScopedEmployeeList((int) $logged_user->id, (int) $logged_user->role_users_id);
        $canUseAttendanceFilters = $this->canUseAttendanceFilters($logged_user);
        $managedEmployeeIds = $canManageScopedAttendance
            ? ManagedEmployeeScope::managedEmployeeIds((int) $logged_user->id)
            : [];

        $companies = $isLocationHead
            ? CompanyScope::companiesForLocationHead((int) $logged_user->id)
            : CompanyScope::companiesForSelect();
        if ($companies->isEmpty()) {
            $companies = company::all('id', 'company_name');
        }

        $start_date = null;
        $end_date = null;
        if ($request->filled('filter_start_date') && $request->filled('filter_end_date')) {
            $start_date = $this->parseAttendanceFilterDate($request->filter_start_date);
            $end_date = $this->parseAttendanceFilterDate($request->filter_end_date);
        }

        if (request()->ajax())
        {
            if (! $start_date || ! $end_date) {
                return datatables()->of([])->make(true);
            }

            if (! $canUseAttendanceFilters) {
                $request->merge(['employee_id' => (int) $logged_user->id]);
            }

            if (!$request->company_id && !$request->department_id && !$request->employee_id
                && !$request->client_id && !$request->location_id)
            {
                $emp_attendance_date_range = [];
            }
            else
            {
                $employee = $this->attendanceEmployeeBaseQuery()
                ->with(['officeShift', 'employeeAttendance' => function ($query) use ($start_date, $end_date)
                {
                    $query->whereBetween('attendance_date', [$start_date, $end_date]);
                },
                    'employeeLeave',
                    'company:id,company_name',
                    'client:id,company_name',
                    'company.companyHolidays'
                ])
                ->select('id', 'company_id', 'client_id', 'first_name', 'last_name', 'office_shift_id', 'joining_date')
                ->where('is_active', '=', 1);

                if ((int) $logged_user->role_users_id === 1) {
                    $this->applyAttendanceEmployeeFilters($employee, $request);
                } elseif ($canManageScopedAttendance) {
                    $employee->whereIn('id', $managedEmployeeIds);
                    $this->applyAttendanceEmployeeFilters($employee, $request);
                } else {
                    $employee->where('id', (int) $logged_user->id);
                }

                $employee = $employee->get();

                $begin = new DateTime($start_date);
                $end = new DateTime($end_date);
                $end->modify('+1 day');
                $interval = DateInterval::createFromDateString('1 day');
                $period   = new DatePeriod($begin, $interval, $end);
                $date_range = [];
                foreach ($period as $dt) {
                    $date_range[] = $dt->format(env('Date_Format'));
                }
                $emp_attendance_date_range = [];

                foreach ($employee as $key1 => $emp) {
                    $all_attendances_array = $emp->employeeAttendance->groupBy('attendance_date')->toArray();
                    $leaves = $emp->employeeLeave;
                    $shift = $emp->officeShift ? $emp->officeShift->toArray() : [];
                    $holidays = $emp->company?->companyHolidays ?? collect();
                    $joining_date = Carbon::parse($emp->joining_date)->format(env('Date_Format'));
                    foreach ($date_range as $key2 => $dt_r) {
                        $emp_attendance_date_range[$key1*count($date_range)+$key2]['id'] = $emp->id;
                        $emp_attendance_date_range[$key1*count($date_range)+$key2]['employee_name'] = ($key2==0) ? '<strong>'.$emp->full_name.'</strong>' : $emp->full_name;
                        $emp_attendance_date_range[$key1*count($date_range)+$key2]['company'] = $this->employeeCompanyLabel($emp);
                        $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_date'] = Carbon::parse($dt_r)->format(env('Date_Format'));

                        //attendance status
                        $day = strtolower(Carbon::parse($dt_r)->format('l')) . '_in';
                        if (strtotime($dt_r) < strtotime($joining_date))
                        {
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = __('Not Join');
                        }
                        elseif (empty($shift[$day]))
                        {
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = __('Off Day');
                        }
                        elseif (array_key_exists($dt_r, $all_attendances_array))
                        {
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = trans('file.present');
                        }
                        else
                        {
                            foreach ($leaves as $leave)
                            {
                                if ($leave->start_date <= $dt_r && $leave->end_date >= $dt_r)
                                {
                                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = __('On Leave');
                                }
                            }
                            foreach ($holidays as $holiday)
                            {
                                if ($holiday->start_date <= $dt_r && $holiday->end_date >= $dt_r)
                                {
                                    $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = __('On Holiday');
                                }
                            }
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['attendance_status'] = trans('Absent');
                        }
                        //attendance status

                        //clock in
                        if (array_key_exists($dt_r, $all_attendances_array))
                        {
                            $first = current($all_attendances_array[$dt_r])['clock_in'];
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['clock_in'] = $first;
                        }
                        else
                        {
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['clock_in'] = '---';
                        }
                        //clock in

                        //clock out
                        if (array_key_exists($dt_r, $all_attendances_array))
                        {
                            $last = end($all_attendances_array[$dt_r])['clock_out'];
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['clock_out'] = $last;
                        }
                        else
                        {
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['clock_out'] = '---';
                        }
                        //clock out

                        //time late
                        if (array_key_exists($dt_r, $all_attendances_array))
                        {
                            $first = current($all_attendances_array[$dt_r])['time_late'];
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['time_late'] = $first;
                        } else
                        {
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['time_late'] = '---';
                        }
                        //time late

                        //early_leaving
                        if (array_key_exists($dt_r, $all_attendances_array))
                        {
                            $last = end($all_attendances_array[$dt_r])['early_leaving'];
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['early_leaving'] = $last;
                        } else
                        {
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['early_leaving'] = '---';
                        }
                        //early_leaving

                        //overtime
                        if (array_key_exists($dt_r, $all_attendances_array))
                        {
                            $total = 0;
                            foreach ($all_attendances_array[$dt_r] as $all_attendance_item)
                            {
                                sscanf($all_attendance_item['overtime'], '%d:%d', $hour, $min);
                                $total += $hour * 60 + $min;
                            }
                            if ($h = floor($total / 60))
                            {
                                $total %= 60;
                            }
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['overtime'] = sprintf('%02d:%02d', $h, $total);
                        } else
                        {
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['overtime'] = '---';
                        }
                        //overtime

                        //total_work
                        if (array_key_exists($dt_r, $all_attendances_array))
                        {
                            $total = 0;
                            foreach ($all_attendances_array[$dt_r] as $all_attendance_item)
                            {
                                sscanf($all_attendance_item['total_work'], '%d:%d', $hour, $min);
                                $total += $hour * 60 + $min;
                            }
                            if ($h = floor($total / 60))
                            {
                                $total %= 60;
                            }
                            $sum_total = 0 + $total;
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['total_work'] = sprintf('%02d:%02d', $h, $total);
                        }
                        else
                        {
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['total_work'] = '---';
                        }
                        //total_work

                        //total_rest
                        if (array_key_exists($dt_r, $all_attendances_array))
                        {
                            $total = 0;
                            foreach ($all_attendances_array[$dt_r] as $all_attendance_item)
                            {
                                //formatting in hour:min and separating them
                                sscanf($all_attendance_item['total_rest'], '%d:%d', $hour, $min);
                                //converting in minute
                                $total += $hour * 60 + $min;
                            }
                            // if minute is greater than hour then $h= hour
                            if ($h = floor($total / 60))
                            {
                                //$total = minute (after excluding hour)
                                $total %= 60;
                            }
                            //returning back to hour:minute format
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['total_rest'] = sprintf('%02d:%02d', $h, $total);
                        } else
                        {
                            $emp_attendance_date_range[$key1*count($date_range)+$key2]['total_rest'] = '---';
                        }
                        //total_rest
                    }
                }
            }

            return datatables()->of($emp_attendance_date_range)
                ->setRowId(function ($row)
                {
                    return $row['id'];
                })
                ->addColumn('employee_name', function ($row)
                {
                    return $row['employee_name'];
                })
                ->addColumn('company', function ($row)
                {
                    return $row['company'];
                })
                ->addColumn('attendance_date', function ($row)
                {
                    return $row['attendance_date'];
                })
                ->addColumn('attendance_status', function ($row)
                {
                    return $row['attendance_status'];
                })
                ->addColumn('clock_in', function ($row)
                {
                    return $row['clock_in'];
                })
                ->addColumn('clock_out', function ($row)
                {
                    return $row['clock_out'];
                })
                ->addColumn('time_late', function ($row)
                {
                    return $row['time_late'];
                })
                ->addColumn('early_leaving', function ($row)
                {
                    return $row['early_leaving'];
                })
                ->addColumn('overtime', function ($row)
                {
                    return $row['overtime'];
                })
                ->addColumn('total_work', function ($row)
                {
                    return $row['total_work'];
                })
                ->addColumn('total_rest', function ($row)
                {
                    return $row['total_rest'];
                })
                ->rawColumns(['action','employee_name'])
                ->make(true);
        }

        return view('timesheet.dateWiseAttendance.index', compact('companies', 'canManageScopedAttendance', 'canUseAttendanceFilters'));

	}


	public function monthlyAttendance(Request $request)
	{
		$logged_user = auth()->user();

        if (! $logged_user->can('monthly-attendances')) {
            return abort(403, __('You are not authorized'));
        }

        $isLocationHead = location::userIsLocationHead((int) $logged_user->id);
        $canManageScopedAttendance = ManagedEmployeeScope::canAccessScopedEmployeeList((int) $logged_user->id, (int) $logged_user->role_users_id);
        $canUseAttendanceFilters = $this->canUseAttendanceFilters($logged_user);
        $managedEmployeeIds = $canManageScopedAttendance
            ? ManagedEmployeeScope::managedEmployeeIds((int) $logged_user->id)
            : [];

		$companies = $isLocationHead
            ? CompanyScope::companiesForLocationHead((int) $logged_user->id)
            : CompanyScope::companiesForSelect();
        if ($companies->isEmpty()) {
            $companies = company::all('id', 'company_name');
        }


		$month_year = $this->parseAttendanceFilterMonthYear($request->filter_month_year);
		$this->date_range = [];
		$this->date_attendance = [];
		$this->date_attendance_iso = [];

		$monthStart = Carbon::parse('first day of '.$month_year);
		$monthEnd = $monthStart->copy()->endOfMonth();
		$first_date = $monthStart->format('Y-m-d');
		$last_date = $monthEnd->format('Y-m-d');
		$dateFormat = env('Date_Format', 'd-m-Y');

		for ($current = $monthStart->copy(); $current->lte($monthEnd); $current->addDay()) {
			$this->date_range[] = $current->format('d D');
			$this->date_attendance_iso[] = $current->format('Y-m-d');
			$this->date_attendance[] = $current->format($dateFormat);
		}


		// if ($logged_user->can('view-attendance'))
		// {
			if ($request->ajax())
			{
				try {
				$this->employeeHolidaysCache = [];

				$employeeQuery = $this->attendanceEmployeeBaseQuery()
				->with(['officeShift', 'department:id,department_name', 'designation:id,designation_name', 'employeeAttendance' => function ($query) use ($first_date, $last_date)
					{
						$query->whereBetween('attendance_date', [$first_date, $last_date]);
					},
						'employeeLeave.LeaveType',
						'company:id,company_name',
						'company.companyHolidays',
						'client:id,company_name',
					])
					->select($this->monthlyEmployeeSelectColumns())
                    ->where('is_active', 1)
                    ->where(function ($query) use ($last_date) {
						$query->whereNull('exit_date')
							->orWhere('exit_date', '>=', $last_date)
							->orWhere('exit_date', '0000-00-00');
					});

				if ((int) $logged_user->role_users_id === 1) {
					if (! $request->filter_company && ! $request->filter_employee
						&& ! $request->filter_client && ! $request->filter_location) {
						return datatables()->of(collect())
							->with(['date_range' => $this->date_range])
							->make(true);
					}

					$this->applyAttendanceEmployeeFilters($employeeQuery, $request, 'filter_');
				} elseif ($canManageScopedAttendance) {
					if ($managedEmployeeIds === []) {
						return datatables()->of(collect())
							->with(['date_range' => $this->date_range])
							->make(true);
					}

					$employeeQuery->whereIn('id', $managedEmployeeIds);
					if ($request->filter_company || $request->filter_employee
						|| $request->filter_client || $request->filter_location) {
						$this->applyAttendanceEmployeeFilters($employeeQuery, $request, 'filter_');
					}
				} else {
					$employeeQuery->where('id', (int) $logged_user->id);
				}

				$employee = $employeeQuery->get();

				return datatables()->of($employee)
					->setRowId(function ($row)
					{
						$this->work_days = 0;
						$this->resetMonthlyAbbreviationCounts();
						$this->currentEmployeeHolidays = $this->holidaysForEmployee($row);

						return $row->id;
					})
					->addColumn('employee_name', function ($row)
					{
						return $row->full_name;
					})
					->addColumn('department_name', function ($row)
					{
						return $row->department->department_name ?? '—';
					})
					->addColumn('designation_name', function ($row)
					{
						return $row->designation->designation_name ?? '—';
					})
					->addColumn('cnic', function ($row)
					{
						return $this->employeesHaveCnicColumn() ? ($row->cnic ?: '—') : '—';
					})
					->addColumn('day1', function ($row)
					{
						return $this->checkAttendanceStatus($row, 0);
					})
					->addColumn('day2', function ($row)
					{
						return $this->checkAttendanceStatus($row, 1);
					})
					->addColumn('day3', function ($row)
					{
						return $this->checkAttendanceStatus($row, 2);
					})
					->addColumn('day4', function ($row)
					{
						return $this->checkAttendanceStatus($row, 3);
					})
					->addColumn('day5', function ($row)
					{
						return $this->checkAttendanceStatus($row, 4);
					})
					->addColumn('day6', function ($row)
					{
						return $this->checkAttendanceStatus($row, 5);
					})
					->addColumn('day7', function ($row)
					{
						return $this->checkAttendanceStatus($row, 6);
					})
					->addColumn('day8', function ($row)
					{
						return $this->checkAttendanceStatus($row, 7);
					})
					->addColumn('day9', function ($row)
					{
						return $this->checkAttendanceStatus($row, 8);
					})
					->addColumn('day10', function ($row)
					{
						return $this->checkAttendanceStatus($row, 9);
					})
					->addColumn('day11', function ($row)
					{
						return $this->checkAttendanceStatus($row, 10);
					})
					->addColumn('day12', function ($row)
					{
						return $this->checkAttendanceStatus($row, 11);
					})
					->addColumn('day13', function ($row)
					{
						return $this->checkAttendanceStatus($row, 12);
					})
					->addColumn('day14', function ($row)
					{
						return $this->checkAttendanceStatus($row, 13);
					})
					->addColumn('day15', function ($row)
					{
						return $this->checkAttendanceStatus($row, 14);
					})
					->addColumn('day16', function ($row)
					{
						return $this->checkAttendanceStatus($row, 15);
					})
					->addColumn('day17', function ($row)
					{
						return $this->checkAttendanceStatus($row, 16);
					})
					->addColumn('day18', function ($row)
					{
						return $this->checkAttendanceStatus($row, 17);
					})
					->addColumn('day19', function ($row)
					{
						return $this->checkAttendanceStatus($row, 18);
					})
					->addColumn('day20', function ($row)
					{
						return $this->checkAttendanceStatus($row, 19);
					})
					->addColumn('day21', function ($row)
					{
						return $this->checkAttendanceStatus($row, 20);
					})
					->addColumn('day22', function ($row)
					{
						return $this->checkAttendanceStatus($row, 21);
					})
					->addColumn('day23', function ($row)
					{
						return $this->checkAttendanceStatus($row, 22);
					})
					->addColumn('day24', function ($row)
					{
						return $this->checkAttendanceStatus($row, 23);
					})
					->addColumn('day25', function ($row)
					{
						return $this->checkAttendanceStatus($row, 24);
					})
					->addColumn('day26', function ($row)
					{
						return $this->checkAttendanceStatus($row, 25);
					})
					->addColumn('day27', function ($row)
					{
						return $this->checkAttendanceStatus($row, 26);
					})
					->addColumn('day28', function ($row)
					{
						return $this->checkAttendanceStatus($row, 27);
					})
					->addColumn('day29', function ($row)
					{
						return $this->checkAttendanceStatus($row, 28);
					})
					->addColumn('day30', function ($row)
					{
						return $this->checkAttendanceStatus($row, 29);
					})
					->addColumn('day31', function ($row)
					{
						return $this->checkAttendanceStatus($row, 30);
					})
					->addColumn('worked_days', function ($row)
					{
						$days = $this->work_days;

						return abs($days - round($days)) < 0.001
							? (string) (int) round($days)
							: number_format($days, 1);
					})
					->addColumn('total_worked_hours', function ($row)
					{
						return $this->totalWorkedHours($row);
					})
					->addColumn('count_p', function ($row)
					{
						return $this->monthlyAbbreviationCount('P');
					})
					->addColumn('count_a', function ($row)
					{
						return $this->monthlyAbbreviationCount('A');
					})
					->addColumn('count_cl', function ($row)
					{
						return $this->monthlyAbbreviationCount('CL');
					})
					->addColumn('count_sl', function ($row)
					{
						return $this->monthlyAbbreviationCount('SL');
					})
					->addColumn('count_ml', function ($row)
					{
						return $this->monthlyAbbreviationCount('ML');
					})
					->addColumn('count_spl', function ($row)
					{
						return $this->monthlyAbbreviationCount('SPL');
					})
					->addColumn('count_wfh', function ($row)
					{
						return $this->monthlyAbbreviationCount('WFH');
					})
					->addColumn('count_lt', function ($row)
					{
						return $this->monthlyAbbreviationCount('LT');
					})
					->addColumn('count_hd', function ($row)
					{
						return $this->monthlyAbbreviationCount('HD');
					})
					->addColumn('count_el', function ($row)
					{
						return $this->monthlyAbbreviationCount('EL');
					})
					->addColumn('count_off', function ($row)
					{
						return $this->monthlyAbbreviationCount('OFF');
					})
					// ->addColumn('total_worked_hours', function ($row) use ($month_year)
					// {
					// 	if ($month_year) {
					// 		return $this->MonthlyTotalWorked($month_year,$row->id);
					// 	}
					// 	else{
					// 		return $this->totalWorkedHours($row);
					// 	}
					// })
					->with([
						'date_range' => $this->date_range,
					])
					->make(true);
				} catch (\Throwable $e) {
					report($e);

					return response()->json([
						'draw' => (int) $request->input('draw', 0),
						'recordsTotal' => 0,
						'recordsFiltered' => 0,
						'data' => [],
						'date_range' => $this->date_range,
						'error' => config('app.debug') ? $e->getMessage() : __('Failed to load monthly attendance.'),
					], 200);
				}
			}

			return view('timesheet.monthlyAttendance.index', compact('companies', 'canManageScopedAttendance', 'canUseAttendanceFilters'));
		// }
		// return response()->json(['success' => __('You are not authorized')]);
	}


	public function checkAttendanceStatus($emp, $index)
	{
		if (count($this->date_attendance) <= $index) {
			return '';
		}

		$displayDate = $this->date_attendance[$index];
		$isoDate = $this->date_attendance_iso[$index] ?? null;

		if (! $isoDate) {
			return '';
		}

		$present = $emp->employeeAttendance->filter(function ($attendance) use ($displayDate) {
			return $attendance->attendance_date === $displayDate;
		});

		$leave = $emp->employeeLeave->filter(function ($leaveItem) use ($isoDate) {
			$start = $this->parseAttendanceFilterDate($leaveItem->start_date);
			$end = $this->parseAttendanceFilterDate($leaveItem->end_date);

			return $start && $end && $isoDate >= $start && $isoDate <= $end;
		});

		$holidays = $this->currentEmployeeHolidays ?? $this->holidaysForEmployee($emp);
		$holiday = $holidays->filter(function ($holidayItem) use ($isoDate) {
			$start = $holidayItem->getAttributes()['start_date'] ?? null;
			$end = $holidayItem->getAttributes()['end_date'] ?? null;

			return $start && $end && $isoDate >= $start && $isoDate <= $end;
		});

		$day = strtolower(Carbon::parse($isoDate)->format('l')) . '_in';
		$isOffDay = ! $emp->officeShift || ! $emp->officeShift->$day;

		if ($present->isNotEmpty()) {
			$firstAttendance = $present->sortBy('clock_in')->first();
			$code = $this->monthlyPresentAbbreviation($firstAttendance);
			$this->incrementMonthlyAbbreviationCount($code);
			$this->countMonthlyWorkedDay($code);

			return $code;
		}

		if ($isOffDay) {
			$this->incrementMonthlyAbbreviationCount('OFF');
			return 'OFF';
		}

		if ($holiday->isNotEmpty()) {
			$this->incrementMonthlyAbbreviationCount('OFF');
			return 'OFF';
		}

		if ($leave->isNotEmpty()) {
			$code = $this->monthlyLeaveAbbreviation($leave->first());
			$this->incrementMonthlyAbbreviationCount($code);
			$this->countMonthlyWorkedDay($code);

			return $code;
		}

		$this->incrementMonthlyAbbreviationCount('A');
		return 'A';
	}

	public function updateAttendance(Request $request)
	{
		$logged_user = auth()->user();
		$companies = company::select('id', 'company_name')->get();
		if ($logged_user->can('edit-attendance'))
		{
			if (request()->ajax())
			{
				$employeeId = (int) $request->employee_id;
				if ($employeeId <= 0) {
					return datatables()->of(collect())->make(true);
				}

				$dateFrom = $this->parseAttendanceFilterDate($request->attendance_date1) ?? now()->format('Y-m-d');
				$dateTo = $this->parseAttendanceFilterDate($request->attendance_date2) ?? $dateFrom;
				if ($dateFrom > $dateTo) {
					[$dateFrom, $dateTo] = [$dateTo, $dateFrom];
				}

				$employee_attendance = Attendance::where('employee_id', $employeeId)
					->whereBetween('attendance_date', [$dateFrom, $dateTo])
					->orderByDesc('attendance_date')
					->orderByDesc('id')
					->get();


				return datatables()->of($employee_attendance)
					->setRowId(function ($row)
					{
						return $row->id;
					})
                    ->addColumn('date', function ($row)
					{
						return $row->attendance_date;
					})
					->addColumn('clock_in', function ($row)
					{
						return $row->clock_in;
					})
					->addColumn('clock_out', function ($row)
					{
						return $row->clock_out;
					})
					->addColumn('action', function ($row)
					{
						if (auth()->user()->can('user-edit'))
						{
							$button = '<button type="button" name="edit" id="' . $row->id . '" class="edit btn btn-primary btn-sm"><i class="dripicons-pencil"></i></button>';
							$button .= '&nbsp;&nbsp;&nbsp;';
							$button .= '<button type="button" name="delete" id="' . $row->id . '" class="delete btn btn-danger btn-sm"><i class="dripicons-trash"></i></button>';

							return $button;
						} else
						{
							return '';
						}
					})
					->rawColumns(['action'])
					->make(true);
			}

			return view('timesheet.updateAttendance.index', compact('companies'));
		}
		return response()->json(['success' => __('You are not authorized')]);
	}

    public function employeeActivityLogs(Request $request)
    {
        $logged_user = auth()->user();
        $companies = company::select('id', 'company_name')->get();

        if (!$logged_user->can('timesheet')) {
            return response()->json(['success' => __('You are not authorized')]);
        }

        if (request()->ajax()) {
            $logs = EmployeeActivityLog::with([
                'employee:id,first_name,last_name',
                'performer:id,username',
            ])->orderByDesc('id');

            if ($request->company_id) {
                $companyEmployeeIds = Employee::where('company_id', $request->company_id)->pluck('id');
                $logs->whereIn('employee_id', $companyEmployeeIds);
            }

            if ($request->employee_id) {
                $logs->where('employee_id', $request->employee_id);
            }

            if ($request->activity_date) {
                $logs->whereDate('created_at', Carbon::parse($request->activity_date)->format('Y-m-d'));
            }

            if (!$logged_user->can('daily-attendances')) {
                $logs->where('employee_id', $logged_user->id);
            }

            return datatables()->of($logs)
                ->setRowId(function ($row) {
                    return $row->id;
                })
                ->addColumn('employee_name', function ($row) {
                    return optional($row->employee)->full_name ?? '---';
                })
                ->addColumn('action', function ($row) {
                    return $row->action ?? '---';
                })
                ->addColumn('description', function ($row) {
                    return $row->description ?? '---';
                })
                ->addColumn('performed_by', function ($row) {
                    return optional($row->performer)->username ?? __('System');
                })
                ->addColumn('ip_address', function ($row) {
                    return $row->ip_address ?? '---';
                })
                ->addColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format(env('Date_Format') . ' H:i');
                })
                ->rawColumns([])
                ->make(true);
        }

        return view('timesheet.activityLogs.index', compact('companies'));
    }

	public function updateAttendanceGet($id)
	{
		$attendance = Attendance::select('id', 'clock_in', 'clock_out', 'attendance_date')
			->findOrFail($id);
        $attendance->clock_in = (new DateTime($attendance->clock_in))->format('h:iA');
        $attendance->clock_out = (new DateTime($attendance->clock_out))->format('h:iA');
		return response()->json(['data' => $attendance]);
	}

	public function updateAttendanceStore(Request $request)
	{
		$data = $this->attendanceHandler($request);

		if (! is_array($data) || empty($data['employee_id'])) {
			return response()->json([
				'errors' => [__('Could not save attendance. Check clock in/out times and the employee office shift for that day.')],
			], 422);
		}

        $attendance = Attendance::create($data);
        $this->logEmployeeActivity((int) $request->employee_id, 'attendance.manual_create', 'Attendance record created manually.', [
            'attendance_id' => $attendance->id,
            'attendance_date' => $data['attendance_date'] ?? null,
            'clock_in' => $data['clock_in'] ?? null,
            'clock_out' => $data['clock_out'] ?? null,
            'time_late' => $data['time_late'] ?? '00:00',
        ]);
		return response()->json(['success' => __('Data is successfully updated')]);
	}

	public function attendanceHandler($request)
	{
		$validator = Validator::make($request->only('attendance_date', 'clock_in', 'clock_out'),
			[
				'attendance_date' => 'required|date',
				'clock_in' => 'required',
				'clock_out' => 'required'
			]);


		if ($validator->fails())
		{
			return response()->json(['errors' => $validator->errors()->all()]);
		}

		$employee_id = $request->employee_id;
		$attendance_date = $request->attendance_date;
		try
		{
			$clock_in = new DateTime($request->clock_in);
			$clock_out = new DateTime($request->clock_out);
		} catch (Exception $e)
		{
			return $e;
		}

        $employee = Employee::with('officeShift')->findOrFail($employee_id);
		$attendance_date_day = Carbon::parse($attendance_date);
		$current_day_in = strtolower($attendance_date_day->format('l')) . '_in';
		$current_day_out = strtolower($attendance_date_day->format('l')) . '_out';
        try
		{
			$shift_in = new DateTime($employee->officeShift->$current_day_in);
            $shift_out = new DateTime($employee->officeShift->$current_day_out);
		} catch (Exception $e)
		{
			return $e;
		}

        $employee_attendance_last = Attendance::where('attendance_date', $attendance_date_day->format('Y-m-d'))
                ->where('employee_id', $employee_id)->orderBy('id', 'desc')->first() ?? null;


        $time_late = '00:00';
        $early_leaving = '00:00';
        $overtime = '00:00';
        $total_work = '00:00';
        $total_rest = '00:00';
        $data = [];
        //if employee attendance record was not found
        if (!$employee_attendance_last)
        {
            $late_cutoff_time = $this->getLateCutoffTime($shift_in);
            // if employee is late
            if ($clock_in > $late_cutoff_time)
            {
                $time_late = $late_cutoff_time->diff($clock_in)->format('%H:%I');
            } // if employee is early or on time
            else
            {
                if(env('ENABLE_EARLY_CLOCKIN') == NULL) {
                    $clock_in = $shift_in;
                }
            }
            if ($clock_out > $shift_in || env('ENABLE_EARLY_CLOCKIN')!=NULL) {
                // if employee is early leaving
                if ($clock_out < $shift_out) {
                    $timeDifference = $shift_out->diff($clock_out)->format('%H:%I');
                    $early_leaving = $timeDifference;
                }

                // calculating total work
                $total_work = $clock_in->diff($clock_out)->format('%H:%I');
                $total_work_dt = new DateTime($total_work);
                // Overtime calculation
                $duty_time = new DateTime($shift_in->diff($shift_out)->format('%H:%I'));
                if ($total_work_dt > $duty_time) {
                    $overtime = $total_work_dt->diff($duty_time)->format('%H:%I');
                }
                $data['employee_id'] = $employee_id;
                $data['attendance_date'] = $attendance_date;
                $data['clock_in'] = $clock_in->format('H:i');
                $data['clock_out'] = $clock_out->format('H:i');
                $data['clock_in_out'] = 0;
                $data['time_late'] = $time_late;
                $data['early_leaving'] = $early_leaving;
                $data['overtime'] = $overtime;
                $data['total_work'] = $total_work;
                $data['attendance_status'] = trans('file.present');
            } else {
                $data['employee_id'] = $employee_id;
                $data['attendance_date'] = $attendance_date;
                $data['clock_in'] = $clock_in->format('H:i');
                $data['clock_out'] = $clock_out->format('H:i');
                $data['clock_in_out'] = 0;
                $data['time_late'] = $time_late;
                $data['early_leaving'] = $early_leaving;
                $data['overtime'] = $overtime;
                $data['total_work'] = $total_work;
                $data['attendance_status'] = trans('file.present');
            }
        }
        // if there is a record of employee attendance
        else {
            // last clock out (needed for calculation rest time)
            $employee_last_clock_out = new DateTime($employee_attendance_last->clock_out);
            $total_rest = $employee_last_clock_out->diff($clock_in)->format('%H:%I');

            // if employee is early leaving
            if ($clock_out < $shift_out) {
                $timeDifference = $shift_out->diff($clock_out)->format('%H:%I');
                $early_leaving = $timeDifference;
            }
            $prev_work = new DateTime($employee_attendance_last->total_work);
            $total_work_dt = $prev_work->add($clock_in->diff($clock_out));
            $total_work = $total_work_dt->format('H:i');
            // Overtime calculation
            $duty_time = new DateTime($shift_in->diff($shift_out)->format('%H:%I'));
            if ($total_work_dt > $duty_time) {
                $overtime = $total_work_dt->diff($duty_time)->format('%H:%I');
            }
            Attendance::whereId($employee_attendance_last->id)->update(['total_work'=> '00:00', 'overtime'=> '00:00']);
            $data['employee_id'] = $employee_id;
            $data['attendance_date'] = $attendance_date;
            $data['clock_in'] = $clock_in->format('H:i');
            $data['clock_out'] = $clock_out->format('H:i');
            $data['clock_in_out'] = 0;
            $data['time_late'] = $time_late;
            $data['early_leaving'] = $early_leaving;
            $data['overtime'] = $overtime;
            $data['total_work'] = $total_work;
            $data['total_rest'] = $total_rest;
            $data['attendance_status'] = trans('file.present');
        }
		return $data;
	}

	public function updateAttendanceUpdate(Request $request)
	{

		$validator = Validator::make($request->only('attendance_date', 'clock_in', 'clock_out'),
			[
				'attendance_date' => 'required|date',
				'clock_in' => 'required',
				'clock_out' => 'required'
			]);


		if ($validator->fails())
		{
			return response()->json(['errors' => $validator->errors()->all()]);
		}

		try
		{
			$clock_in = new DateTime($request->clock_in);
			$clock_out = new DateTime($request->clock_out);
		} catch (Exception $e)
		{
			return $e;
		}

        if ($clock_in > $clock_out) {
            return response()->json(['errors' => [__('Clock in cannot be greater than clock out')]]);
        }

        $id = $request->hidden_id;
        $employee_id = $request->employee_id;
		$attendance_date = $request->attendance_date;
        $employee = Employee::with('officeShift')->findOrFail($employee_id);
		$attendance_date_day = Carbon::parse($attendance_date);
		$current_day_in = strtolower($attendance_date_day->format('l')) . '_in';
		$current_day_out = strtolower($attendance_date_day->format('l')) . '_out';

        try
		{
			$shift_in = new DateTime($employee->officeShift->$current_day_in);
            $shift_out = new DateTime($employee->officeShift->$current_day_out);
		} catch (Exception $e)
		{
			return $e;
		}

        $employee_attendance = Attendance::where('employee_id', $employee_id)
        ->whereDate('attendance_date', $attendance_date_day->format('Y-m-d'))
        ->get()->toArray();
        $no_emp_att = count($employee_attendance);


        $time_late = '00:00';
        $early_leaving = '00:00';
        $overtime = '00:00';
        $total_work = '00:00';
        $total_rest = '00:00';
        $data = [];
        $late_cutoff_time = $this->getLateCutoffTime($shift_in);

        for ($i=0; $i < $no_emp_att; $i++) {
            if ($employee_attendance[$i]['id'] == $id) {
				// if employee is late
				if ($clock_in > $late_cutoff_time)
				{
					if ($i == 0) {
						$time_late = $late_cutoff_time->diff($clock_in)->format('%H:%I');
					}
				} // if employee is early or on time
				else
				{
					if(env('ENABLE_EARLY_CLOCKIN') == NULL) {
						$clock_in = $shift_in;
					}
				}
				if ($clock_out > $shift_in || env('ENABLE_EARLY_CLOCKIN')!=NULL) {
					// if employee is early leaving
					if ($clock_out < $shift_out) {
						$timeDifference = $shift_out->diff($clock_out)->format('%H:%I');
						$early_leaving = $timeDifference;
					}

					// calculating total work
					$total_work = $clock_in->diff($clock_out)->format('%H:%I');
					$total_work_dt = new DateTime($total_work);
					// Overtime calculation
					$duty_time = new DateTime($shift_in->diff($shift_out)->format('%H:%I'));

					$data['employee_id'] = $employee_id;
					$data['attendance_date'] = $attendance_date;
					$data['clock_in'] = $clock_in->format('H:i');
					$data['clock_out'] = $clock_out->format('H:i');
					$data['clock_in_out'] = 0;
					$data['time_late'] = $time_late;
					$data['early_leaving'] = $early_leaving;

					if ($no_emp_att > 1) {
						if ($i != $no_emp_att-1) {
							$next_clock_in = (new DateTime($employee_attendance[$i+1]['clock_in']));
							if ($clock_out > $next_clock_in) {
								return response()->json(['errors' => [__('Clock out cannot be greater than next clock in')]]);
							}
							else {
								$total_rest = $clock_out->diff($next_clock_in)->format('%H:%I');
								Attendance::find($employee_attendance[$i+1]['id'])->update(['total_rest'=> $total_rest]);
							}
						}
						if ($i != 0) {
							$prev_clock_out = (new DateTime($employee_attendance[$i-1]['clock_out']));
							if ($clock_in < $prev_clock_out) {
								return response()->json(['errors' => [__('Clock in cannot be lower than previous clock out')]]);
							}
							else {
								$total_rest = $prev_clock_out->diff($clock_in)->format('%H:%I');
								Attendance::find($employee_attendance[$i]['id'])->update(['total_rest'=> $total_rest]);
							}
						}

						$before_change_clock_in = new DateTime($employee_attendance[$i]['clock_in']);
						$before_change_clock_out = new DateTime($employee_attendance[$i]['clock_out']);
						$before_change_work = new DateTime($before_change_clock_in->diff($before_change_clock_out)->format('%H:%I'));
						$before_change_total_work = new DateTime($employee_attendance[$no_emp_att-1]['total_work']);
						$total_work_dt = $total_work_dt->add($before_change_work->diff($before_change_total_work));
						$total_work = $total_work_dt->format('H:i');

						if ($total_work_dt > $duty_time) {
							$overtime = $total_work_dt->diff($duty_time)->format('%H:%I');
						}
						Attendance::find($employee_attendance[$no_emp_att-1]['id'])->update(['total_work'=> $total_work, 'overtime'=> $overtime]);
					}
					else {
						if ($total_work_dt > $duty_time) {
							$overtime = $total_work_dt->diff($duty_time)->format('%H:%I');
						}
						$data['overtime'] = $overtime;
						$data['total_work'] = $total_work;
					}

					Attendance::find($employee_attendance[$i]['id'])->update($data);
                    $this->logEmployeeActivity($employee_id, 'attendance.manual_update', 'Attendance record updated manually.', [
                        'attendance_id' => $employee_attendance[$i]['id'],
                        'attendance_date' => $attendance_date,
                        'clock_in' => $data['clock_in'] ?? null,
                        'clock_out' => $data['clock_out'] ?? null,
                        'time_late' => $data['time_late'] ?? '00:00',
                    ]);
					return response()->json(['success' => __('Data is successfully updated')]);
				}
				else
				{
					return response()->json(['errors' => ['Clock out can not be lower than Shift in']]);
				}
                break;
            }
        }
	}

	public function updateAttendanceDelete($id)
	{
		$logged_user = auth()->user();

		if ($logged_user->can('delete-attendance'))
		{
            $deleted_att_info = Attendance::find($id);

            $clock_in = new DateTime($deleted_att_info->clock_in);
            $clock_out = new DateTime($deleted_att_info->clock_out);

            $employee_id = $deleted_att_info->employee_id;
            $attendance_date = $deleted_att_info->attendance_date;
            $employee = Employee::with('officeShift')->findOrFail($employee_id);
            $attendance_date_day = Carbon::parse($attendance_date);
            $current_day_in = strtolower($attendance_date_day->format('l')) . '_in';
            $current_day_out = strtolower($attendance_date_day->format('l')) . '_out';

            try
            {
                $shift_in = new DateTime($employee->officeShift->$current_day_in);
                $shift_out = new DateTime($employee->officeShift->$current_day_out);
            } catch (Exception $e)
            {
                return $e;
            }

            $employee_attendance = Attendance::where('employee_id', $employee_id)
            ->whereDate('attendance_date', $attendance_date_day->format('Y-m-d'))
            ->get()->toArray();
            $no_emp_att = count($employee_attendance);

            for ($i=0; $i < $no_emp_att; $i++) {
                if ($employee_attendance[$i]['id'] == $id) {
                    if ($no_emp_att > 1) {
                        if ($i == 0) {
							$time_late = '00:00';
							$next_clock_in = (new DateTime($employee_attendance[$i+1]['clock_in']));
                            $late_cutoff_time = $this->getLateCutoffTime($shift_in);
							// if employee is late
							if ($next_clock_in > $late_cutoff_time) {
								$time_late = $late_cutoff_time->diff($next_clock_in)->format('%H:%I');
							}
                            Attendance::find($employee_attendance[$i+1]['id'])->update(['time_late'=> $time_late, 'total_rest'=> '00:00']);
                        }
                        elseif ($i != $no_emp_att-1) {
                            $prev_clock_out = (new DateTime($employee_attendance[$i-1]['clock_out']));
                            $next_clock_in = (new DateTime($employee_attendance[$i+1]['clock_in']));
                            $total_rest = $prev_clock_out->diff($next_clock_in)->format('%H:%I');
                            Attendance::find($employee_attendance[$i+1]['id'])->update(['total_rest'=> $total_rest]);
                        }
                        // Overtime calculation
                        $duty_time = new DateTime($shift_in->diff($shift_out)->format('%H:%I'));
                        $before_delete_work = new DateTime($clock_in->diff($clock_out)->format('%H:%I'));
                        $before_delete_total_work = new DateTime($employee_attendance[$no_emp_att-1]['total_work']);
                        $total_work = $before_delete_work->diff($before_delete_total_work)->format('%H:%I');
                        $total_work_dt = new DateTime($total_work);
                        $overtime = '00:00';
                        if ($total_work_dt > $duty_time) {
                            $overtime = $total_work_dt->diff($duty_time)->format('%H:%I');
                        }

                        if ($i == $no_emp_att-1) {
                            Attendance::find($employee_attendance[$no_emp_att-2]['id'])->update(['total_work'=> $total_work, 'overtime'=> $overtime]);
                        }
                        else {
                            Attendance::find($employee_attendance[$no_emp_att-1]['id'])->update(['total_work'=> $total_work, 'overtime'=> $overtime]);
                        }
                    }
                    Attendance::whereId($id)->delete();
                    $this->logEmployeeActivity($employee_id, 'attendance.manual_delete', 'Attendance record deleted manually.', [
                        'attendance_id' => $id,
                        'attendance_date' => $attendance_date,
                        'clock_in' => $deleted_att_info->clock_in ?? null,
                        'clock_out' => $deleted_att_info->clock_out ?? null,
                    ]);
                    return response()->json(['success' => __('Data is successfully deleted')]);
                    break;
                }
            }
		}
		return response()->json(['error' => __('You are not authorized')]);
	}


	public function import()
	{
		$logged_user = auth()->user();
		if ($logged_user->can('delete-attendance'))
		{
			return view('timesheet.attendance.import');
		}
		return abort(404,__('You are not authorized'));
	}

    public function importDeviceCsv()
	{
        if (!env('USER_VERIFIED'))
		{
            $this->setErrorMessage('This feature is disabled for demo!');
            return redirect()->back();
		}
		try
		{
			Excel::queueImport(new AttendancesImportDevice(), request()->file('file'));
		} catch (ValidationException $e)
		{
			$failures = $e->failures();

            $error_msg = '';
            foreach ($failures as $failure) {
                $error_msg.= '<h4>Row No -'.$failure->row().'</h4>';
                foreach ($failure->errors() as $error) {
                    $error_msg.= '<li>'.$error.'</li>';
                }
            }
            $this->setErrorMessage($error_msg);
            return back();
		}
		$this->setSuccessMessage(__('Imported Successfully'));
		return back();
	}

	public function importPost()
	{
        if (!env('USER_VERIFIED'))
		{
            $this->setErrorMessage('This feature is disabled for demo!');
            return redirect()->back();
		}
		try
		{
			Excel::queueImport(new AttendancesImport(), request()->file('file'));
		} catch (ValidationException $e)
		{
			$failures = $e->failures();

            $error_msg = '';
            foreach ($failures as $failure) {
                $error_msg.= '<h4>Row No -'.$failure->row().'</h4>';
                foreach ($failure->errors() as $error) {
                    $error_msg.= '<li>'.$error.'</li>';
                }
            }
            $this->setErrorMessage($error_msg);
            return back();
		}
		$this->setSuccessMessage(__('Imported Successfully'));
		return back();
	}


	protected function MonthlyTotalWorked($month_year,$employeeId)
	{
		$year = date('Y',strtotime($month_year));
		$month = date('m',strtotime($month_year));

		$total = 0;

		$att = Employee::with(['employeeAttendance' => function ($query) use ($year,$month){
				$query->whereYear('attendance_date',$year)->whereMonth('attendance_date',$month);
			}])
			->select('id', 'company_id', 'first_name', 'last_name', 'office_shift_id')
			->whereId($employeeId)
			->get();

		//$count = count($att[0]->employeeAttendance);
		// return $att[0]->employeeAttendance[0]->total_work;

		foreach ($att[0]->employeeAttendance as $key => $a)
		{
			// return $att[0]->employeeAttendance[1]->total_work;
			// return $a->total_work;
			sscanf($a->total_work, '%d:%d', $hour, $min);
			$total += $hour * 60 + $min;
		}

		if ($h = floor($total / 60))
		{
			$total %= 60;
		}
		$sum_total = sprintf('%02d:%02d', $h, $total);

		return $sum_total;
	}

}
