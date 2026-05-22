<?php

namespace App\Http\traits;

use App\Models\User;
use App\Notifications\EmployeeWelcomeCredentialsNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

trait SendsEmployeeCredentialsTrait
{
    protected function sendEmployeeCredentialsEmail(User $user, string $plainPassword, string $staffId): void
    {
        $email = strtolower(trim((string) $user->email));
        if ($email === '') {
            return;
        }

        try {
            dispatch(function () use ($user, $plainPassword, $staffId, $email) {
                Notification::route('mail', $email)
                    ->notify(new EmployeeWelcomeCredentialsNotification(
                        trim($user->first_name.' '.$user->last_name),
                        (string) $user->username,
                        $plainPassword,
                        $staffId,
                        route('login')
                    ));
            })->afterResponse();
        } catch (Throwable $e) {
            Log::warning('Failed to queue employee credentials email', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
