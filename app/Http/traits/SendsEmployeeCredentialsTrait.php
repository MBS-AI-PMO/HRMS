<?php

namespace App\Http\traits;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

trait SendsEmployeeCredentialsTrait
{
    /**
     * Send login credentials immediately (no queue).
     */
    protected function sendEmployeeCredentialsEmail(User $user, string $plainPassword, string $staffId): bool
    {
        $email = strtolower(trim((string) $user->email));
        if ($email === '') {
            Log::warning('Employee credentials email skipped: empty email', ['user_id' => $user->id]);

            return false;
        }

        $employeeName = trim($user->first_name.' '.$user->last_name);
        $loginUrl = route('login');

        try {
            Mail::send(
                'emails.employee_credentials',
                [
                    'employeeName' => $employeeName,
                    'username' => (string) $user->username,
                    'password' => $plainPassword,
                    'staffId' => $staffId,
                    'loginUrl' => $loginUrl,
                ],
                function ($message) use ($email, $employeeName) {
                    $message->to($email)
                        ->subject(__('Your HRMS account credentials'));
                }
            );

            Log::info('Employee credentials email sent (direct)', ['email' => $email, 'user_id' => $user->id]);

            return true;
        } catch (Throwable $e) {
            Log::error('Employee credentials email failed', [
                'email' => $email,
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
