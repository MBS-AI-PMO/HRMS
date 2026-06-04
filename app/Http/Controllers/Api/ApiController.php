<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Country;
use App\Models\department;
use App\Models\Employee;
use App\Models\EmployeeActivityLog;
use App\Models\EmployeeLeaveTypeDetail;
use App\Models\GeneralSetting;
use App\Models\IpSetting;
use App\Models\leave;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveNotifier;
use App\Notifications\LeaveNotificationToAdmin;
use App\Notifications\WfhEventNotification;
use App\Notifications\WfhRequestNotificationToApprover;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApiController extends Controller
{
    private function getTodayShiftTimes(Employee $employee): array
    {
        $day = strtolower(Carbon::now()->format('l'));
        $inKey = $day.'_in';
        $outKey = $day.'_out';

        $shift = $employee->officeShift;
        if (! $shift || empty($shift->$inKey) || empty($shift->$outKey)) {
            return [null, null];
        }

        return [$shift->$inKey, $shift->$outKey];
    }

    private function getIpPrefix(string $ip): string
    {
        $parts = explode('.', $ip);
        return implode('.', array_slice($parts, 0, 3));
    }

    private function checkAttendanceTypeRules(Request $request, Employee $employee): ?array
    {
        if ($employee->attendance_type === 'ip_based') {
            $ipSettings = IpSetting::all();
            if ($ipSettings->isEmpty()) {
                return ['status' => false, 'message' => 'Office IP is not configured.', 'code' => 422];
            }

            $clientIpPrefix = $this->getIpPrefix($request->ip());
            $allowed = $ipSettings->contains(function ($ipRow) use ($clientIpPrefix) {
                return $this->getIpPrefix((string) $ipRow->ip_address) === $clientIpPrefix;
            });

            if (! $allowed) {
                return ['status' => false, 'message' => 'Please connect to office internet for attendance.', 'code' => 403];
            }
        }

        if ($employee->attendance_type === 'location_based') {
            $validated = $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);

            $general = GeneralSetting::latest()->first();
            if (! $general || $general->latitude === null || $general->longitude === null) {
                return ['status' => false, 'message' => 'Office location is not configured.', 'code' => 422];
            }

            $distance = $this->calculateDistanceMeters(
                (float) $general->latitude,
                (float) $general->longitude,
                (float) $validated['latitude'],
                (float) $validated['longitude']
            );

            $maxRadius = (float) ($general->max_radius ?? 25);
            if ($distance > $maxRadius) {
                return ['status' => false, 'message' => 'You are outside the allowed office radius.', 'code' => 403];
            }
        }

        return null;
    }

    private function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function login(Request $request)
    {
      
        try {
            $validated = $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            $user = User::where('username', $validated['username'])->first();

            if (! $user || ! Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials.',
                ], 401);
            }

            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Login successful.',
                'token' => $token,
                'user' => $user,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Login failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => true,
                'message' => 'Logout successful.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Logout failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function profile(Request $request)
    {
        try {
            return response()->json([
                'status' => true,
                'data' => $request->user(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Profile fetch failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:191',
                'last_name' => 'sometimes|string|max:191',
                'username' => ['sometimes', 'string', 'max:64', Rule::unique('users', 'username')->ignore($user->id)],
                'email' => ['sometimes', 'nullable', 'email', 'max:64', Rule::unique('users', 'email')->ignore($user->id)],
                'contact_no' => ['sometimes', 'string', 'max:15', Rule::unique('users', 'contact_no')->ignore($user->id)],
                'password' => 'nullable|string|min:4|confirmed',
                'profile_photo' => 'nullable|image|max:10240|mimes:jpeg,png,jpg,gif',

                /* Employee-only (synced alongside user; HR IDs stay server-side). */
                'gender' => 'sometimes|nullable|string|max:191',
                'marital_status' => 'sometimes|nullable|string|max:191',
                'address' => 'sometimes|nullable|string|max:2000',
                'city' => 'sometimes|nullable|string|max:191',
                'state' => 'sometimes|nullable|string|max:191',
                'country' => 'sometimes|nullable|string|max:191',
                'zip_code' => 'sometimes|nullable|string|max:191',
                'date_of_birth' => ['sometimes', 'nullable', 'string', 'max:32'],
            ]);

            if (isset($validated['profile_photo']) && $validated['profile_photo'] instanceof UploadedFile) {
                $photo = $validated['profile_photo'];
                $baseName = $validated['username'] ?? $user->username;
                $file_name = preg_replace('/\s+/', '', $baseName).'_'.time().'.'.$photo->getClientOriginalExtension();
                $photo->storeAs('profile_photos', $file_name);
                $validated['profile_photo'] = $file_name;
            }

            if ($request->filled('password')) {
                $validated['password'] = Hash::make($request->password);
            } else {
                unset($validated['password']);
            }
            unset($validated['password_confirmation']);

            if (isset($validated['username'])) {
                $validated['username'] = strtolower(trim($validated['username']));
            }
            if (array_key_exists('email', $validated) && $validated['email'] !== null) {
                $validated['email'] = strtolower(trim($validated['email']));
            }

            $updatable = collect($validated)->filter(function ($value) {
                return $value !== null && $value !== '';
            })->all();

            if ($updatable === []) {
                return response()->json([
                    'status' => false,
                    'message' => 'No fields to update.',
                ], 422);
            }

            $userKeys = ['first_name', 'last_name', 'username', 'email', 'contact_no', 'password', 'profile_photo'];
            $employeeSyncKeys = [
                'first_name', 'last_name', 'email', 'contact_no',
                'gender', 'marital_status', 'address', 'city', 'state', 'country', 'zip_code', 'date_of_birth',
            ];

            $userPayload = Arr::only($updatable, $userKeys);
            $employeePayload = Arr::only($updatable, $employeeSyncKeys);

            DB::transaction(function () use ($user, $userPayload, $employeePayload) {
                if ($userPayload !== []) {
                    $user->fill($userPayload);
                    $user->save();
                }

                $employee = Employee::find($user->id);
                if ($employee && $employeePayload !== []) {
                    $employee->update($employeePayload);
                }
            });

            $user->refresh();

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully.',
                'data' => $user->makeHidden(['password', 'remember_token']),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Profile update failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function employeeDetails()
    {
        try {
            $id = auth()->user()->id;
            $employee = Employee::with([
                'company:id,company_name',
                'department:id,department_name',
                'designation:id,designation_name',
                'officeShift:id,shift_name',
                'status:id,status_title',
                'role:id,name',
            ])->find($id);

            if (! $employee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Employee not found.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $this->formatEmployeeForProfileApi($employee),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Employee detail fetch failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Profile / mobile app: human-readable labels (not raw country/company IDs).
     */
    private function formatEmployeeForProfileApi(Employee $employee): array
    {
        $employee->loadMissing([
            'company:id,company_name',
            'department:id,department_name',
            'designation:id,designation_name',
            'officeShift:id,shift_name',
            'status:id,status_title',
            'role:id,name',
        ]);

        $data = $employee->toArray();

        $data['company_name'] = $employee->company?->company_name;
        $data['department_name'] = $employee->department?->department_name;
        $data['designation_name'] = $employee->designation?->designation_name;
        $data['office_shift_name'] = $employee->officeShift?->shift_name;
        $data['status_title'] = $employee->status?->status_title;
        $data['role_name'] = $employee->role?->name;

        $countryId = $employee->country;
        if ($countryId !== null && $countryId !== '') {
            $data['country_name'] = Country::whereKey($countryId)->value('name')
                ?? (is_numeric($countryId) ? null : (string) $countryId);
        } else {
            $data['country_name'] = null;
        }

        return $data;
    }

    public function administrators()
    {
        try {
            $admins = User::where('role_users_id', 1)->latest('id')->get();

            return response()->json([
                'status' => true,
                'count' => $admins->count(),
                'data' => $admins,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Administrators fetch failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logged-in user ki attendance list (employee_id = auth id).
     * Optional query: date_from, date_to (Y-m-d).
     */
    public function myAttendances(Request $request)
    {
        try {
            $userId = (int) $request->user()->id;

            $validated = $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
            ]);

            if (! empty($validated['date_from']) && ! empty($validated['date_to'])
                && $validated['date_from'] > $validated['date_to']) {
                return response()->json([
                    'status' => false,
                    'message' => 'date_from must be on or before date_to.',
                ], 422);
            }

            $query = Attendance::query()
                ->where('employee_id', $userId)
                ->select([
                    'id',
                    'employee_id',
                    'attendance_date',
                    'clock_in',
                    'clock_in_ip',
                    'clock_out',
                    'clock_out_ip',
                    'clock_in_out',
                    'time_late',
                    'early_leaving',
                    'overtime',
                    'total_work',
                    'total_rest',
                    'attendance_status',
                ])
                ->orderByDesc('attendance_date')
                ->orderByDesc('id');

            if (! empty($validated['date_from'])) {
                $query->whereDate('attendance_date', '>=', $validated['date_from']);
            }
            if (! empty($validated['date_to'])) {
                $query->whereDate('attendance_date', '<=', $validated['date_to']);
            }

            $attendances = $query->get();

            return response()->json([
                'status' => true,
                'user_id' => $userId,
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
                'count' => $attendances->count(),
                'data' => $attendances->map(fn ($row) => $this->formatAttendanceForApi($row))->values(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Attendance fetch failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mobile-friendly attendance row (history tab + calendar dots).
     */
    private function formatAttendanceForApi(Attendance $row): array
    {
        $rawDate = $row->getAttributes()['attendance_date'] ?? null;
        $dateIso = $rawDate
            ? Carbon::parse($rawDate)->format('Y-m-d')
            : null;

        $clockIn = $this->formatClockTimeForApi($row->clock_in ?? null);
        $clockOut = $this->formatClockTimeForApi($row->clock_out ?? null);
        $displayStatus = $this->resolveAttendanceDisplayStatus($row);

        return [
            'id' => (int) $row->id,
            'employee_id' => (int) $row->employee_id,
            'attendance_date' => $row->attendance_date,
            'attendance_date_iso' => $dateIso,
            'clock_in' => $row->clock_in,
            'clock_out' => $row->clock_out,
            'clock_in_display' => $clockIn,
            'clock_out_display' => $clockOut,
            'clock_in_out' => $row->clock_in_out,
            'time_late' => $row->time_late,
            'early_leaving' => $row->early_leaving,
            'overtime' => $row->overtime,
            'total_work' => $row->total_work,
            'total_rest' => $row->total_rest,
            'attendance_status' => $row->attendance_status,
            'display_status' => $displayStatus,
            'duration_label' => $this->formatTotalWorkLabel($row->total_work ?? null),
        ];
    }

    private function resolveAttendanceDisplayStatus(Attendance $row): string
    {
        $status = strtolower(trim((string) ($row->attendance_status ?? '')));
        $clockIn = trim((string) ($row->clock_in ?? ''));

        if ($clockIn === '' || str_contains($status, 'absent')) {
            return 'absent';
        }

        $late = trim((string) ($row->time_late ?? ''));
        if ($late !== '' && ! in_array($late, ['00:00', '00:00:00', '---'], true)) {
            return 'late';
        }

        return 'present';
    }

    private function formatClockTimeForApi(?string $time): ?string
    {
        if ($time === null) {
            return null;
        }

        $time = trim($time);
        if ($time === '' || $time === '---') {
            return null;
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, $time)->format('h:i A');
            } catch (Throwable $e) {
                continue;
            }
        }

        return $time;
    }

    private function formatTotalWorkLabel(?string $totalWork): ?string
    {
        if ($totalWork === null) {
            return null;
        }

        $totalWork = trim($totalWork);
        if ($totalWork === '' || $totalWork === '00:00' || $totalWork === '---') {
            return null;
        }

        $parts = explode(':', $totalWork);
        if (count($parts) < 2) {
            return $totalWork;
        }

        $hours = (int) $parts[0];
        $minutes = (int) $parts[1];
        if ($hours <= 0 && $minutes <= 0) {
            return null;
        }

        if ($minutes === 0) {
            return $hours.'h';
        }

        return $hours.'h '.$minutes.'m';
    }

    public function clockIn(Request $request)
    {
        try {
            if (env('ENABLE_CLOCKIN_CLOCKOUT') === null) {
                return response()->json([
                    'status' => false,
                    'message' => 'Clock in/out is disabled.',
                ], 403);
            }

            $user = $request->user();
            $employee = Employee::with('officeShift')->find($user->id);
            if (! $employee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Employee profile not found.',
                ], 404);
            }

            [$shiftInValue, $shiftOutValue] = $this->getTodayShiftTimes($employee);
            if (! $shiftInValue || ! $shiftOutValue) {
                return response()->json([
                    'status' => false,
                    'message' => 'No office shift assigned for today.',
                ], 422);
            }

            $typeError = $this->checkAttendanceTypeRules($request, $employee);
            if ($typeError) {
                return response()->json([
                    'status' => false,
                    'message' => $typeError['message'],
                ], $typeError['code']);
            }

            $today = now()->format('Y-m-d');
            $dateFmt = env('Date_Format', 'd-m-Y');
            // Mobile sends `attendance_date` as `Date_Format` so Model mutator can parse it; fallback = server today.
            $attendanceDateForMutator = now()->format($dateFmt);
            $postedDate = $request->input('attendance_date');
            if (is_string($postedDate) && $postedDate !== '') {
                try {
                    $parsedYmD = Carbon::createFromFormat($dateFmt, $postedDate)->format('Y-m-d');
                    if ($parsedYmD === $today) {
                        $attendanceDateForMutator = $postedDate;
                    }
                } catch (\Throwable $e) {
                    // invalid client date — keep server default
                }
            }

            $last = Attendance::where('attendance_date', $today)
                ->where('employee_id', $employee->id)
                ->orderByDesc('id')
                ->first();

            if ($last && (int) $last->clock_in_out === 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Already clocked in. Please clock out first.',
                ], 422);
            }

            $shiftIn = new \DateTime($shiftInValue);
            $currentTime = new \DateTime(now()->format('H:i'));

            $data = [
                'employee_id' => $employee->id,
                'attendance_date' => $attendanceDateForMutator,
                'clock_in' => $currentTime->format('H:i'),
                'clock_in_out' => 1,
                'clock_in_ip' => $request->ip(),
                'attendance_status' => 'present',
                'time_late' => '00:00',
                'total_rest' => '00:00',
                'total_work' => '00:00',
                'overtime' => '00:00',
                'early_leaving' => '00:00',
            ];

            if ($currentTime > $shiftIn) {
                $data['time_late'] = $shiftIn->diff(new \DateTime($data['clock_in']))->format('%H:%I');
            } elseif (env('ENABLE_EARLY_CLOCKIN') === null) {
                $data['clock_in'] = $shiftIn->format('H:i');
            }

            if ($last && (int) $last->clock_in_out === 0 && ! empty($last->clock_out)) {
                $lastClockOut = new \DateTime($last->clock_out);
                $data['total_rest'] = $lastClockOut->diff(new \DateTime($data['clock_in']))->format('%H:%I');
                $data['total_work'] = $last->total_work;
                $data['overtime'] = $last->overtime;
                Attendance::whereKey($last->id)->update(['total_work' => '00:00', 'overtime' => '00:00']);
            }

            $attendance = Attendance::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Clocked in successfully.',
                'data' => $attendance,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Clock in failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function clockOut(Request $request)
    {
        try {
            if (env('ENABLE_CLOCKIN_CLOCKOUT') === null) {
                return response()->json([
                    'status' => false,
                    'message' => 'Clock in/out is disabled.',
                ], 403);
            }

            $user = $request->user();
            $employee = Employee::with('officeShift')->find($user->id);
            if (! $employee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Employee profile not found.',
                ], 404);
            }

            [$shiftInValue, $shiftOutValue] = $this->getTodayShiftTimes($employee);
            if (! $shiftInValue || ! $shiftOutValue) {
                return response()->json([
                    'status' => false,
                    'message' => 'No office shift assigned for today.',
                ], 422);
            }

            $typeError = $this->checkAttendanceTypeRules($request, $employee);
            if ($typeError) {
                return response()->json([
                    'status' => false,
                    'message' => $typeError['message'],
                ], $typeError['code']);
            }

            $today = now()->format('Y-m-d');
            $attendance = Attendance::where('attendance_date', $today)
                ->where('employee_id', $employee->id)
                ->orderByDesc('id')
                ->first();

            if (! $attendance || (int) $attendance->clock_in_out !== 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'No active clock-in found for today.',
                ], 422);
            }

            $shiftIn = new \DateTime($shiftInValue);
            $shiftOut = new \DateTime($shiftOutValue);
            $currentTime = new \DateTime(now()->format('H:i'));

            if ($currentTime <= $shiftIn && env('ENABLE_EARLY_CLOCKIN') === null) {
                Attendance::whereKey($attendance->id)->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Clock-out rejected before shift start; attendance reset.',
                ]);
            }

            $clockOut = $currentTime->format('H:i');
            $clockIn = new \DateTime($attendance->clock_in);
            $prevWork = new \DateTime($attendance->total_work);
            $totalWork = $prevWork->add($clockIn->diff(new \DateTime($clockOut)));
            $dutyTime = new \DateTime($shiftIn->diff($shiftOut)->format('%H:%I'));

            $updateData = [
                'clock_out' => $clockOut,
                'clock_out_ip' => $request->ip(),
                'clock_in_out' => 0,
                'total_work' => $totalWork->format('H:i'),
            ];

            if ($currentTime < $shiftOut) {
                $updateData['early_leaving'] = $shiftOut->diff(new \DateTime($clockOut))->format('%H:%I');
            }

            if ($totalWork > $dutyTime) {
                $updateData['overtime'] = $totalWork->diff($dutyTime)->format('%H:%I');
            }

            $attendance->update($updateData);
            $attendance->refresh();

            return response()->json([
                'status' => true,
                'message' => 'Clocked out successfully.',
                'data' => $attendance,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Clock out failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Office / site settings from general_settings (geofence, branding, timezone).
     */
    public function officeInfo()
    {
        try {
            $general = GeneralSetting::latest()->first();

            if (! $general) {
                return response()->json([
                    'status' => false,
                    'message' => 'General settings not found.',
                ], 404);
            }

            $logo = $general->site_logo;
            $siteLogoUrl = $logo
                ? asset('images/logo/'.$logo)
                : null;

            $data = [
                'site_title' => $general->site_title,
                'site_logo' => $logo,
                'site_logo_url' => $siteLogoUrl,
                'latitude' => $general->latitude,
                'longitude' => $general->longitude,
                'min_radius' => $general->min_radius,
                'max_radius' => $general->max_radius,
                'time_zone' => $general->time_zone,
                'date_format' => $general->date_format,
                'currency' => $general->currency,
                'currency_format' => $general->currency_format,
                'footer' => $general->footer,
                'footer_link' => $general->footer_link,
            ];

            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Office info fetch failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Leave types for apply-leave form (excludes WFH types).
     */
    public function leaveTypes(Request $request)
    {
        try {
            $wfhOnly = $request->boolean('wfh');
            $user = $request->user();
            $userId = (int) $user->id;
            $employeeId = $this->resolveEmployeeIdForLeaveApi($user);

            if ($wfhOnly) {
                $types = LeaveType::query()
                    ->select('id', 'leave_type', 'allocated_day')
                    ->orderBy('leave_type')
                    ->get()
                    ->filter(fn ($type) => $this->isWfhLeaveTypeName((string) $type->leave_type))
                    ->values()
                    ->map(fn ($type) => [
                        'id' => $type->id,
                        'leave_type' => $type->leave_type,
                        'allocated_day' => (int) $type->allocated_day,
                        'remaining_allocated_day' => (int) $type->allocated_day,
                        'is_wfh' => true,
                    ]);

                if ($types->isEmpty()) {
                    $fallback = LeaveType::firstOrCreate(
                        ['leave_type' => 'WFH'],
                        ['allocated_day' => 365, 'company_id' => null]
                    );
                    $types = collect([[
                        'id' => $fallback->id,
                        'leave_type' => $fallback->leave_type,
                        'allocated_day' => (int) $fallback->allocated_day,
                        'remaining_allocated_day' => (int) $fallback->allocated_day,
                        'is_wfh' => true,
                    ]]);
                }
            } else {
                if (! $employeeId) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Employee profile not found.',
                    ], 404);
                }
                $types = collect($this->employeePortalLeaveTypes($employeeId));
            }

            return response()->json([
                'status' => true,
                'user_id' => $userId,
                'employee_id' => $employeeId ?? $userId,
                'count' => $types->count(),
                'data' => $types->values(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Leave types fetch failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remaining leave balance per type (same source as employee profile).
     */
    public function leaveBalances(Request $request)
    {
        try {
            $user = $request->user();
            $userId = (int) $user->id;
            $employeeId = $this->resolveEmployeeIdForLeaveApi($user) ?? $userId;
            $balances = array_map(
                fn (array $row) => [
                    'leave_type_id' => (int) ($row['id'] ?? 0),
                    'leave_type' => (string) ($row['leave_type'] ?? ''),
                    'allocated_day' => (int) ($row['allocated_day'] ?? 0),
                    'remaining_allocated_day' => (int) ($row['remaining_allocated_day'] ?? 0),
                ],
                $this->employeePortalLeaveTypes($employeeId)
            );

            return response()->json([
                'status' => true,
                'user_id' => $userId,
                'count' => count($balances),
                'data' => $balances,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Leave balances fetch failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logged-in employee leave requests (non-WFH).
     */
    public function myLeaves(Request $request)
    {
        return $this->myLeaveRequests($request, false);
    }

    /**
     * Single leave request detail (must belong to auth user).
     */
    public function showLeave(Request $request, $id)
    {
        return $this->showMyLeaveRequest($request, (int) $id, false);
    }

    /**
     * Submit a leave request (status pending).
     */
    public function storeLeave(Request $request)
    {
        return $this->storeMyLeaveRequest($request, false);
    }

    /**
     * Logged-in employee WFH requests.
     */
    public function myWfhRequests(Request $request)
    {
        return $this->myLeaveRequests($request, true);
    }

    /**
     * Single WFH request detail.
     */
    public function showWfhRequest(Request $request, $id)
    {
        return $this->showMyLeaveRequest($request, (int) $id, true);
    }

    /**
     * Submit a WFH request (status + HR/manager approval pending).
     */
    public function storeWfhRequest(Request $request)
    {
        return $this->storeMyLeaveRequest($request, true);
    }

    private function myLeaveRequests(Request $request, bool $wfhOnly)
    {
        try {
            $userId = (int) $request->user()->id;

            $validated = $request->validate([
                'status' => 'nullable|string|max:40',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
            ]);

            if (! empty($validated['date_from']) && ! empty($validated['date_to'])
                && $validated['date_from'] > $validated['date_to']) {
                return response()->json([
                    'status' => false,
                    'message' => 'date_from must be on or before date_to.',
                ], 422);
            }

            $query = leave::query()
                ->with([
                    'LeaveType:id,leave_type,allocated_day',
                    'department:id,department_name',
                    'company:id,company_name',
                ])
                ->where('employee_id', $userId)
                ->orderByDesc('id');

            $this->applyWfhScope($query, $wfhOnly);

            if (! empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }
            if (! empty($validated['date_from'])) {
                $query->whereDate('end_date', '>=', $validated['date_from']);
            }
            if (! empty($validated['date_to'])) {
                $query->whereDate('start_date', '<=', $validated['date_to']);
            }

            $rows = $query->get()->map(fn ($row) => $this->formatLeaveForApi($row));

            return response()->json([
                'status' => true,
                'user_id' => $userId,
                'count' => $rows->count(),
                'data' => $rows,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $wfhOnly ? 'WFH requests fetch failed.' : 'Leave requests fetch failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function showMyLeaveRequest(Request $request, int $id, bool $expectWfh)
    {
        try {
            $userId = (int) $request->user()->id;

            $query = leave::query()
                ->with([
                    'LeaveType:id,leave_type,allocated_day',
                    'department:id,department_name',
                    'company:id,company_name',
                    'employee:id,first_name,last_name',
                ])
                ->where('employee_id', $userId)
                ->whereKey($id);

            $this->applyWfhScope($query, $expectWfh);

            $leave = $query->first();

            if (! $leave) {
                return response()->json([
                    'status' => false,
                    'message' => $expectWfh ? 'WFH request not found.' : 'Leave request not found.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $this->formatLeaveForApi($leave, true),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $expectWfh ? 'WFH request fetch failed.' : 'Leave request fetch failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * company_id / department_id required on leaves table — resolve from employee profile.
     *
     * @return array{ok: true, company_id: int, department_id: int}|array{ok: false, message: string}
     */
    private function resolveEmployeeLeaveOrgIds(Employee $employee): array
    {
        $companyId = $employee->company_id ? (int) $employee->company_id : null;
        $departmentId = $employee->department_id ? (int) $employee->department_id : null;

        if (! $companyId) {
            $companyId = (int) (DB::table('companies')->orderBy('id')->value('id') ?? 0);
        }

        if (! $departmentId && $companyId) {
            $departmentId = (int) (department::where('company_id', $companyId)->orderBy('id')->value('id') ?? 0);
        }

        if (! $departmentId) {
            $departmentId = (int) (department::orderBy('id')->value('id') ?? 0);
        }

        if (! $companyId || ! $departmentId) {
            return [
                'ok' => false,
                'message' => 'Company or department is not set on your employee profile. Please contact HR to update your profile.',
            ];
        }

        return [
            'ok' => true,
            'company_id' => $companyId,
            'department_id' => $departmentId,
        ];
    }

    private function storeMyLeaveRequest(Request $request, bool $forceWfh)
    {
        try {
            $user = $request->user();
            $employeeId = $this->resolveEmployeeIdForLeaveApi($user);
            $employee = $employeeId ? Employee::find($employeeId) : null;

            if (! $employee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Employee profile not found.',
                ], 404);
            }

            $orgIds = $this->resolveEmployeeLeaveOrgIds($employee);
            if (! $orgIds['ok']) {
                return response()->json([
                    'status' => false,
                    'message' => $orgIds['message'],
                ], 422);
            }

            $validated = $request->validate([
                'leave_type_id' => 'nullable|integer|exists:leave_types,id',
                'start_date' => 'required|string',
                'end_date' => 'required|string',
                'leave_reason' => 'required|string|min:1|max:5000',
                'remarks' => 'nullable|string|max:191',
            ]);

            $startYmD = $this->parseApiDateToYmd($validated['start_date']);
            $endYmD = $this->parseApiDateToYmd($validated['end_date']);

            if ($startYmD > $endYmD) {
                return response()->json([
                    'status' => false,
                    'message' => 'end_date must be on or after start_date.',
                ], 422);
            }

            $today = now()->format('Y-m-d');
            if ($startYmD < $today || $endYmD < $today) {
                return response()->json([
                    'status' => false,
                    'message' => 'Start and end dates cannot be in the past.',
                ], 422);
            }

            $leaveTypeId = (int) ($validated['leave_type_id'] ?? 0);

            if ($forceWfh) {
                $wfhType = LeaveType::firstOrCreate(
                    ['leave_type' => 'WFH'],
                    ['allocated_day' => 365, 'company_id' => null]
                );
                if ($leaveTypeId === 0 || ! $this->isWfhLeaveTypeId($leaveTypeId)) {
                    $leaveTypeId = (int) $wfhType->id;
                }
            } else {
                if ($leaveTypeId === 0) {
                    return response()->json([
                        'status' => false,
                        'message' => 'leave_type_id is required.',
                    ], 422);
                }
                if ($this->isWfhLeaveTypeId($leaveTypeId)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Use WFH API to submit work-from-home requests.',
                    ], 422);
                }

                if (! $this->employeeHasLeaveType($employee->id, $leaveTypeId)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'This leave type is not assigned to you in the portal.',
                    ], 422);
                }
            }

            $leaveType = LeaveType::findOrFail($leaveTypeId);
            $totalDays = $this->calculateInclusiveDays($startYmD, $endYmD);
            $isWfhLeave = $this->isWfhLeaveTypeId($leaveTypeId);

            if (! $isWfhLeave) {
                $quotaError = $this->validateLeaveQuota((int) $employee->id, $leaveTypeId, $totalDays, (int) $leaveType->allocated_day);
                if ($quotaError) {
                    return response()->json([
                        'status' => false,
                        'message' => $quotaError,
                    ], 422);
                }
            }

            $dateFmt = env('Date_Format', 'd-m-Y');
            $data = [
                'employee_id' => $employee->id,
                'company_id' => $orgIds['company_id'],
                'department_id' => $orgIds['department_id'],
                'leave_type_id' => $leaveTypeId,
                'leave_reason' => $validated['leave_reason'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'status' => 'pending',
                'is_notify' => null,
                'start_date' => Carbon::createFromFormat('Y-m-d', $startYmD)->format($dateFmt),
                'end_date' => Carbon::createFromFormat('Y-m-d', $endYmD)->format($dateFmt),
                'total_days' => $totalDays,
            ];

            if ($isWfhLeave) {
                $data['hr_approval_status'] = 'pending';
                $data['manager_approval_status'] = 'pending';
            }

            $leave = leave::create($data);

            EmployeeActivityLog::write(
                (int) $leave->employee_id,
                (int) $user->id,
                $isWfhLeave ? 'wfh.requested' : 'leave.requested',
                $isWfhLeave ? 'WFH request submitted.' : 'Leave request submitted.',
                [
                    'leave_id' => $leave->id,
                    'leave_type_id' => $leave->leave_type_id,
                    'status' => $leave->status,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'total_days' => $leave->total_days,
                ],
                $request->ip()
            );

            $leave->load('employee:id,first_name,last_name');

            try {
                if ($isWfhLeave) {
                    $this->notifyWfhEventApi($leave, 'requested');
                } else {
                    LeaveNotifier::notify($leave, 'requested');
                }
            } catch (Throwable $notifyError) {
                // Request is saved; do not fail API if mail/notification breaks.
                \Illuminate\Support\Facades\Log::warning('Leave/WFH notification failed: '.$notifyError->getMessage());
            }

            $leave->load(['LeaveType:id,leave_type', 'department:id,department_name', 'company:id,company_name']);

            $formatted = $this->formatLeaveForApi($leave, true);
            if ($forceWfh) {
                $formatted['is_wfh'] = true;
                $formatted['hr_approval_status'] = $leave->hr_approval_status ?? 'pending';
                $formatted['manager_approval_status'] = $leave->manager_approval_status ?? 'pending';
            }

            return response()->json([
                'status' => true,
                'message' => $isWfhLeave ? 'WFH request submitted successfully.' : 'Leave request submitted successfully.',
                'data' => $formatted,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $forceWfh ? 'WFH request failed.' : 'Leave request failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function wfhLeaveTypeIds(): array
    {
        return LeaveType::query()
            ->pluck('id')
            ->filter(fn ($id) => $this->isWfhLeaveTypeId((int) $id))
            ->values()
            ->all();
    }

    private function applyWfhScope($query, bool $wfhOnly): void
    {
        if ($wfhOnly) {
            $wfhTypeIds = $this->wfhLeaveTypeIds();
            $query->where(function ($q) use ($wfhTypeIds) {
                // WFH rows always get HR/manager approval columns on create (portal parity).
                $q->whereNotNull('hr_approval_status')
                    ->orWhereNotNull('manager_approval_status');
                $q->orWhereHas('LeaveType', function ($typeQuery) {
                    $typeQuery->where('leave_type', 'like', '%wfh%')
                        ->orWhere('leave_type', 'like', '%work from home%');
                });
                if ($wfhTypeIds !== []) {
                    $q->orWhereIn('leave_type_id', $wfhTypeIds);
                }
            });
        } else {
            $query->where(function ($outer) {
                $outer->whereNull('hr_approval_status')
                    ->whereNull('manager_approval_status');
                $outer->where(function ($subQuery) {
                    $subQuery->whereDoesntHave('LeaveType', function ($q) {
                        $q->where('leave_type', 'like', '%wfh%')
                            ->orWhere('leave_type', 'like', '%work from home%');
                    })->orWhereNull('leave_type_id');
                });
            });
        }
    }

    private function isWfhLeaveRecord(leave $leave): bool
    {
        if ($leave->hr_approval_status !== null || $leave->manager_approval_status !== null) {
            return true;
        }

        return $this->isWfhLeaveTypeId((int) ($leave->leave_type_id ?? 0));
    }

    private function normalizeLeaveApiStatus(leave $leave): string
    {
        $status = strtolower(trim((string) ($leave->status ?? 'pending')));
        if ($status === 'approve') {
            $status = 'approved';
        }
        if (in_array($status, ['approved', 'rejected'], true)) {
            return $status;
        }
        if (str_contains($status, 'reject')) {
            return 'rejected';
        }
        if (str_contains($status, 'first level')) {
            return 'first level approval';
        }

        $manager = strtolower(trim((string) ($leave->manager_approval_status ?? '')));
        $hr = strtolower(trim((string) ($leave->hr_approval_status ?? '')));

        if ($manager === 'rejected' || $hr === 'rejected') {
            return 'rejected';
        }
        if ($manager === 'approved' && ($hr === 'approved' || $hr === '')) {
            return 'approved';
        }
        if ($manager === 'approved' && $hr === 'pending') {
            return 'first level approval';
        }

        return $status !== '' ? $status : 'pending';
    }

    private function formatLeaveForApi(leave $leave, bool $detailed = false): array
    {
        $attrs = $leave->getAttributes();
        $payload = [
            'id' => (int) $leave->id,
            'employee_id' => (int) $leave->employee_id,
            'company_id' => (int) $leave->company_id,
            'department_id' => (int) $leave->department_id,
            'leave_type_id' => $leave->leave_type_id ? (int) $leave->leave_type_id : null,
            'leave_type' => $leave->LeaveType?->leave_type,
            'department_name' => $leave->department?->department_name,
            'company_name' => $leave->company?->company_name,
            'start_date' => $leave->start_date,
            'end_date' => $leave->end_date,
            'start_date_iso' => isset($attrs['start_date']) ? Carbon::parse($attrs['start_date'])->format('Y-m-d') : null,
            'end_date_iso' => isset($attrs['end_date']) ? Carbon::parse($attrs['end_date'])->format('Y-m-d') : null,
            'total_days' => (int) $leave->total_days,
            'leave_reason' => $leave->leave_reason,
            'remarks' => $leave->remarks,
            'status' => $this->normalizeLeaveApiStatus($leave),
            'hr_approval_status' => $leave->hr_approval_status ?? null,
            'manager_approval_status' => $leave->manager_approval_status ?? null,
            'is_wfh' => $this->isWfhLeaveRecord($leave),
            'applied_at' => $leave->created_at,
            'created_at' => $leave->created_at,
            'updated_at' => $leave->updated_at,
        ];

        if ($detailed && $leave->relationLoaded('employee')) {
            $payload['employee_name'] = $leave->employee->full_name ?? null;
        }

        return $payload;
    }

    private function parseApiDateToYmd(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new Exception('Date is required.');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
        }

        $dateFmt = env('Date_Format', 'd-m-Y');

        return Carbon::createFromFormat($dateFmt, $value)->format('Y-m-d');
    }

    private function calculateInclusiveDays(string $startYmD, string $endYmD): int
    {
        $start = new DateTime($startYmD);
        $end = new DateTime($endYmD);

        return (int) $start->diff($end)->days + 1;
    }

    /**
     * Employee id for leave APIs (portal uses employees.id, same as users.id in most installs).
     */
    private function resolveEmployeeIdForLeaveApi(User $user): ?int
    {
        $id = (int) $user->id;
        if (Employee::whereKey($id)->exists()) {
            return $id;
        }

        if (! empty($user->email)) {
            $byEmail = (int) (Employee::where('email', $user->email)->value('id') ?? 0);
            if ($byEmail > 0) {
                return $byEmail;
            }
        }

        return null;
    }

    /**
     * Leave types configured for employee in HR portal (remaining leave tab).
     */
    private function employeePortalLeaveTypes(int $employeeId): array
    {
        $types = [];
        $detail = EmployeeLeaveTypeDetail::where('employee_id', $employeeId)->first();

        if ($detail && $detail->leave_type_detail) {
            $rows = @unserialize($detail->leave_type_detail);
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $parsed = $this->parsePortalLeaveTypeRow($row);
                    if ($parsed !== null) {
                        $types[] = $parsed;
                    }
                }
            }
        }

        if ($types === []) {
            $types = $this->buildEmployeeLeaveTypesFromMaster($employeeId);
        }

        return $this->syncLeaveTypeRemainingFromApproved($employeeId, $types);
    }

    /**
     * Align remaining days with approved leave usage when portal serialize is stale.
     */
    private function syncLeaveTypeRemainingFromApproved(int $employeeId, array $types): array
    {
        foreach ($types as $index => $row) {
            $typeId = (int) ($row['id'] ?? 0);
            if ($typeId <= 0) {
                continue;
            }
            $allocated = (int) ($row['allocated_day'] ?? 0);
            $stored = (int) ($row['remaining_allocated_day'] ?? $allocated);
            $used = (int) leave::query()
                ->where('employee_id', $employeeId)
                ->where('leave_type_id', $typeId)
                ->where('status', 'approved')
                ->sum('total_days');
            $calculated = max(0, $allocated - $used);
            $remaining = min($stored, $calculated);
            $types[$index]['remaining_allocated_day'] = $remaining;
            $types[$index]['has_balance'] = $remaining > 0;
        }

        return $types;
    }

    private function parsePortalLeaveTypeRow(array $row): ?array
    {
        $name = (string) ($row['leave_type'] ?? '');
        if ($this->isWfhLeaveTypeName($name)) {
            return null;
        }

        $leaveTypeId = (int) ($row['leave_type_id'] ?? $row['id'] ?? 0);
        if ($leaveTypeId <= 0 && $name !== '') {
            $leaveTypeId = (int) (LeaveType::where('leave_type', $name)->value('id') ?? 0);
        }
        if ($leaveTypeId <= 0) {
            return null;
        }

        if ($name === '') {
            $name = (string) (LeaveType::whereKey($leaveTypeId)->value('leave_type') ?? '');
        }
        if ($this->isWfhLeaveTypeName($name)) {
            return null;
        }

        $allocated = (int) ($row['allocated_day'] ?? 0);
        $remaining = (int) ($row['remaining_allocated_day'] ?? $row['remaining'] ?? -1);
        if ($remaining < 0) {
            $remaining = $allocated;
        }

        return [
            'id' => $leaveTypeId,
            'leave_type' => $name,
            'allocated_day' => $allocated,
            'remaining_allocated_day' => $remaining,
            'is_wfh' => false,
            'has_balance' => $remaining > 0,
        ];
    }

    /**
     * Fallback when employee_leave_type_details row is missing (build like LeaveTypeDataManageTrait).
     */
    private function buildEmployeeLeaveTypesFromMaster(int $employeeId): array
    {
        $employee = Employee::with('employeeLeave')->find($employeeId);
        if (! $employee) {
            return [];
        }

        $types = [];
        $leaveTypes = LeaveType::query()
            ->select('id', 'leave_type', 'allocated_day')
            ->orderBy('leave_type')
            ->get();

        foreach ($leaveTypes as $item) {
            $name = (string) $item->leave_type;
            if ($this->isWfhLeaveTypeName($name)) {
                continue;
            }

            $totalPaid = (int) $employee->employeeLeave
                ->where('leave_type_id', $item->id)
                ->sum('total_days');
            $allocated = (int) $item->allocated_day;
            $remaining = max(0, $allocated - $totalPaid);

            $types[] = [
                'id' => (int) $item->id,
                'leave_type' => $name,
                'allocated_day' => $allocated,
                'remaining_allocated_day' => $remaining,
                'is_wfh' => false,
                'has_balance' => $remaining > 0,
            ];
        }

        return $types;
    }

    private function employeeHasLeaveType(int $employeeId, int $leaveTypeId): bool
    {
        foreach ($this->employeePortalLeaveTypes($employeeId) as $row) {
            if ((int) ($row['id'] ?? 0) === $leaveTypeId) {
                return true;
            }
        }

        return false;
    }

    private function validateLeaveQuota(int $employeeId, int $leaveTypeId, int $requestedDays, int $allocatedDay): ?string
    {
        if (! $this->employeeHasLeaveType($employeeId, $leaveTypeId)) {
            return 'This leave type is not assigned to you in the portal.';
        }

        if ($allocatedDay > 0 && $requestedDays > $allocatedDay) {
            return 'Insufficient allocated days for this leave type.';
        }

        foreach ($this->employeePortalLeaveTypes($employeeId) as $row) {
            if ((int) ($row['id'] ?? 0) === $leaveTypeId) {
                $remaining = (int) ($row['remaining_allocated_day'] ?? 0);
                if ($requestedDays > $remaining) {
                    return 'Allocated quota for this leave type is less than requested total days.';
                }

                return null;
            }
        }

        return null;
    }

    private function isWfhLeaveTypeName(string $name): bool
    {
        $name = strtolower($name);

        return str_contains($name, 'wfh') || str_contains($name, 'work from home');
    }

    private function isWfhLeaveTypeId(int $leaveTypeId): bool
    {
        if (! $leaveTypeId) {
            return false;
        }

        $leaveType = LeaveType::find($leaveTypeId);

        if (! $leaveType) {
            return false;
        }

        return $this->isWfhLeaveTypeName((string) $leaveType->leave_type);
    }

    private function notifyWfhEventApi(leave $leave, string $event): void
    {
        $employee = User::find($leave->employee_id);
        $departmentHeadId = department::where('id', $leave->department_id)->value('department_head');
        $departmentHeadUser = $departmentHeadId ? User::find($departmentHeadId) : null;
        $roleIds = DB::table('role_has_permissions')->where('permission_id', 294)->pluck('role_id');
        $roleIds[] = 1;
        $permissionUsers = User::query()->whereIn('role_users_id', $roleIds)->get();

        $link = route('leaves.index');
        if ($event === 'requested') {
            $subject = 'WFH request submitted';
            $message = 'A new WFH request has been submitted.';
        } elseif ($event === 'approved') {
            $subject = 'WFH request approved';
            $message = 'WFH request has been approved.';
        } elseif ($event === 'rejected') {
            $subject = 'WFH request rejected';
            $message = 'WFH request has been rejected.';
        } else {
            $subject = 'WFH request updated';
            $message = 'WFH request status is pending.';
        }

        $leave->loadMissing('employee');
        $requestorName = optional($leave->employee)->full_name ?? 'Employee';
        $eventMessage = $message.' ('.$requestorName.')';

        $recipients = collect()
            ->merge($permissionUsers);

        if ($departmentHeadUser) {
            $recipients->push($departmentHeadUser);
        }
        if ($employee) {
            $recipients->push($employee);
        }

        $recipients = $recipients->filter()->unique('id');

        foreach ($recipients as $recipient) {
            $recipientLink = (int) $recipient->id === (int) $leave->employee_id
                ? route('profile').'#WFH'
                : $link;

            $recipient->notify(new WfhRequestNotificationToApprover($eventMessage, $recipientLink));

            try {
                $recipient->notify(new WfhEventNotification($subject, $eventMessage, $recipientLink));
            } catch (Throwable $e) {
                // keep in-app notification even if mail fails
            }
        }
    }

    /**
     * Portal database notifications for logged-in user (same as header bell).
     */
    public function myNotifications(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'limit' => 'nullable|integer|min:1|max:100',
                'unread_only' => 'nullable|boolean',
            ]);

            $limit = (int) ($validated['limit'] ?? 50);

            $query = $user->notifications()->orderByDesc('created_at');

            if ($request->boolean('unread_only')) {
                $query->whereNull('read_at');
            }

            $rows = $query->limit($limit)->get();

            return response()->json([
                'status' => true,
                'unread_count' => $user->unreadNotifications()->count(),
                'count' => $rows->count(),
                'data' => $rows->map(fn ($row) => $this->formatNotificationForApi($row))->values(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Notifications fetch failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unread count for app badge (portal: unreadNotifications->count()).
     */
    public function notificationsUnreadCount(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'status' => true,
                'unread_count' => $user->unreadNotifications()->count(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unread count fetch failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark one notification read (id) or all when id omitted.
     */
    public function markNotificationsRead(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'id' => 'nullable|uuid',
            ]);

            if (! empty($validated['id'])) {
                $notification = $user->notifications()->where('id', $validated['id'])->first();

                if (! $notification) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Notification not found.',
                    ], 404);
                }

                $notification->markAsRead();
            } else {
                $user->unreadNotifications->markAsRead();
            }

            return response()->json([
                'status' => true,
                'message' => 'Notifications marked as read.',
                'unread_count' => $user->unreadNotifications()->count(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Mark read failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all notifications (portal: clearAll).
     */
    public function clearNotifications(Request $request)
    {
        try {
            $user = $request->user();
            $user->notifications()->delete();

            return response()->json([
                'status' => true,
                'message' => 'All notifications cleared.',
                'unread_count' => 0,
                'count' => 0,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Clear notifications failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function formatNotificationForApi($notification): array
    {
        $payload = $notification->data;
        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?? [];
        }
        if (! is_array($payload)) {
            $payload = [];
        }

        $message = (string) ($payload['data'] ?? $payload['message'] ?? $payload['body'] ?? '');
        if ($message === '') {
            $message = 'Notification';
        }

        $link = $payload['link'] ?? null;
        if ($link !== null) {
            $link = (string) $link;
            if ($link === '') {
                $link = null;
            }
        }

        $typeShort = class_basename((string) $notification->type);

        return [
            'id' => (string) $notification->id,
            'type' => (string) $notification->type,
            'type_label' => $this->notificationTypeLabel($typeShort),
            'message' => trim(strip_tags($message)),
            'link' => $link,
            'read_at' => $notification->read_at,
            'is_read' => $notification->read_at !== null,
            'created_at' => $notification->created_at,
            'created_at_human' => $notification->created_at
                ? Carbon::parse($notification->created_at)->diffForHumans()
                : null,
        ];
    }

    private function notificationTypeLabel(string $typeShort): string
    {
        $labels = [
            'LeaveNotification' => 'Leave',
            'LeaveNotificationToAdmin' => 'Leave',
            'WfhRequestNotificationToApprover' => 'WFH',
            'AnnouncementPublished' => 'Announcement',
            'CompanyPolicyNotify' => 'Policy',
            'EmployeeAwardNotify' => 'Award',
            'EmployeePromotion' => 'Promotion',
            'EmployeeTransferNotify' => 'Transfer',
            'EmployeeTravelStatus' => 'Travel',
            'EmployeeWarningNotify' => 'Warning',
            'EmployeeResignationNotify' => 'Resignation',
            'EmployeeTerminationNotify' => 'Termination',
            'EventNotify' => 'Event',
            'MeetingNotify' => 'Meeting',
            'ComplaintFromNotify' => 'Complaint',
            'ComplainAgainstNotify' => 'Complaint',
            'TicketCreatedNotification' => 'Ticket',
            'TicketUpdatedNotification' => 'Ticket',
            'TicketAssignedNotification' => 'Ticket',
            'ProjectUpdatedNotification' => 'Project',
            'ProjectCreatedNotifiaction' => 'Project',
        ];

        if (isset($labels[$typeShort])) {
            return $labels[$typeShort];
        }

        $label = preg_replace('/Notification$/', '', $typeShort) ?? $typeShort;

        return trim(preg_replace('/([a-z])([A-Z])/', '$1 $2', $label) ?? $typeShort);
    }
}
