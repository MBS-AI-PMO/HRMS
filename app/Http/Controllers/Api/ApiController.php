<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\GeneralSetting;
use App\Models\IpSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

            DB::transaction(function () use ($user, $updatable) {
                $user->fill($updatable);
                $user->save();

                $employee = Employee::find($user->id);
                if ($employee) {
                    $sync = collect($updatable)->only(['first_name', 'last_name', 'email', 'contact_no'])->all();
                    if ($sync !== []) {
                        $employee->update($sync);
                    }
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
            $employee = Employee::find($id);

            if (! $employee) {
                return response()->json([
                    'status' => false,
                    'message' => 'Employee not found.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $employee,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Employee detail fetch failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
                'count' => $attendances->count(),
                'data' => $attendances,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Attendance fetch failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
                'attendance_date' => $today,
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
}
