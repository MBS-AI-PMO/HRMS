<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApiController extends Controller
{
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

    public function employees()
    {
        try {
            $employees = Employee::query()->latest('id')->get();

            return response()->json([
                'status' => true,
                'count' => $employees->count(),
                'data' => $employees,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Employees fetch failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function employeeDetails($id)
    {
        try {
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
