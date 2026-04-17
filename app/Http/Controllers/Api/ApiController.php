<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

    public function attendanceList()
    {
        try {
            $attendances = Attendance::query()
                ->select([
                    'id',
                    'employee_id',
                    'attendance_date',
                    'clock_in',
                    'clock_out',
                    'attendance_status',
                    'total_work',
                    'total_rest',
                ])
                ->latest('id')
                ->get();

            return response()->json([
                'status' => true,
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
}
