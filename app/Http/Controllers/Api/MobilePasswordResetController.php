<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Scopes\AuthCompanyScope;
use App\Support\MailConfig;
use App\Support\PasswordRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Mobile-app-only password reset via email OTP.
 * Uses cache — does NOT touch password_resets / web portal reset flow.
 */
class MobilePasswordResetController extends Controller
{
    private const OTP_TTL_MINUTES = 10;

    private const THROTTLE_SECONDS = 60;

    private const MAX_ATTEMPTS = 5;

    public function forgotPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|max:255',
            ]);

            $email = strtolower(trim($validated['email']));
            $genericMessage = 'If an account exists for this email, a reset code has been sent.';

            if (Cache::has($this->throttleKey($email))) {
                return response()->json([
                    'status' => true,
                    'message' => $genericMessage,
                    'expires_in_minutes' => self::OTP_TTL_MINUTES,
                ]);
            }

            $user = $this->findUserByEmail($email);

            if ($user) {
                $otp = (string) random_int(100000, 999999);

                Cache::put($this->otpKey($email), [
                    'user_id' => (int) $user->id,
                    'otp_hash' => Hash::make($otp),
                    'attempts' => 0,
                ], now()->addMinutes(self::OTP_TTL_MINUTES));

                Cache::put(
                    $this->throttleKey($email),
                    1,
                    now()->addSeconds(self::THROTTLE_SECONDS)
                );

                $this->sendOtpEmail($email, $otp, $user);
            }

            return response()->json([
                'status' => true,
                'message' => $genericMessage,
                'expires_in_minutes' => self::OTP_TTL_MINUTES,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('[MOBILE OTP] forgot-password failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not process password reset request. Please try again.',
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|max:255',
                'otp' => 'required|string|size:6',
                'password' => PasswordRules::required(),
            ]);

            $email = strtolower(trim($validated['email']));
            $otp = trim($validated['otp']);
            $payload = Cache::get($this->otpKey($email));

            if (! is_array($payload) || empty($payload['otp_hash']) || empty($payload['user_id'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Reset code is invalid or has expired. Please request a new one.',
                ], 422);
            }

            $attempts = (int) ($payload['attempts'] ?? 0);
            if ($attempts >= self::MAX_ATTEMPTS) {
                Cache::forget($this->otpKey($email));

                return response()->json([
                    'status' => false,
                    'message' => 'Too many invalid attempts. Please request a new reset code.',
                ], 422);
            }

            if (! Hash::check($otp, (string) $payload['otp_hash'])) {
                $payload['attempts'] = $attempts + 1;
                Cache::put(
                    $this->otpKey($email),
                    $payload,
                    now()->addMinutes(self::OTP_TTL_MINUTES)
                );

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid reset code.',
                ], 422);
            }

            $user = User::query()->find((int) $payload['user_id']);
            if (! $user) {
                Cache::forget($this->otpKey($email));

                return response()->json([
                    'status' => false,
                    'message' => 'Account not found.',
                ], 404);
            }

            $user->password = Hash::make($validated['password']);
            $user->save();

            try {
                $user->tokens()->delete();
            } catch (Throwable $e) {
                // Sanctum may be unavailable in some envs; password already updated.
            }

            Cache::forget($this->otpKey($email));
            Cache::forget($this->throttleKey($email));

            return response()->json([
                'status' => true,
                'message' => 'Password updated successfully. You can now log in.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('[MOBILE OTP] reset-password failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Could not reset password. Please try again.',
            ], 500);
        }
    }

    private function findUserByEmail(string $email): ?User
    {
        $user = User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        if ($user) {
            return $user;
        }

        $employeeId = Employee::withoutGlobalScope(AuthCompanyScope::class)
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->value('id');

        if (! $employeeId) {
            return null;
        }

        return User::query()->find((int) $employeeId);
    }

    private function sendOtpEmail(string $email, string $otp, User $user): void
    {
        MailConfig::boot();

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        if ($name === '') {
            $name = $user->username ?? 'Employee';
        }

        $minutes = self::OTP_TTL_MINUTES;
        $body = implode("\n", [
            "Hello {$name},",
            '',
            'You requested a password reset from the EMS mobile app.',
            '',
            "Your reset code is: {$otp}",
            '',
            "This code expires in {$minutes} minutes.",
            '',
            'If you did not request this, you can ignore this email.',
            '',
            '— EMS',
        ]);

        Mail::raw($body, function ($message) use ($email) {
            $message->to($email)
                ->subject('EMS mobile password reset code');
        });
    }

    private function otpKey(string $email): string
    {
        return 'mobile_password_otp:'.$email;
    }

    private function throttleKey(string $email): string
    {
        return 'mobile_password_otp_throttle:'.$email;
    }
}
