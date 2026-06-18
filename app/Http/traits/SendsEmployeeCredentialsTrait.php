<?php

namespace App\Http\traits;

use App\Models\Employee;
use App\Models\User;
use App\Services\NotificationRecipientResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

trait SendsEmployeeCredentialsTrait
{
    /**
     * Build organization details for the credentials email from an employee record.
     */
    protected function credentialsDetailsFromEmployee(Employee $employee): array
    {
        $employee->loadMissing([
            'company:id,company_name',
            'department:id,department_name',
            'designation:id,designation_name',
            'officeShift:id,shift_name',
        ]);

        return [
            'registered_email' => NotificationRecipientResolver::resolveUserEmailAddress((int) $employee->id)
                ?? strtolower(trim((string) $employee->email)),
            'company_name' => $employee->company->company_name ?? '—',
            'department_name' => $employee->department->department_name ?? '—',
            'designation_name' => $employee->designation->designation_name ?? '—',
            'office_shift_name' => $employee->officeShift->shift_name ?? '—',
        ];
    }

    /**
     * Send login credentials immediately (no queue).
     *
     * @param  array<string, mixed>  $details  Optional: registered_email, company_name, department_name, designation_name, office_shift_name
     */
    protected function sendEmployeeCredentialsEmail(User $user, string $plainPassword, string $staffId, array $details = []): bool
    {
        $email = NotificationRecipientResolver::resolveUserEmailAddress((int) $user->id);

        if ($email === null) {
            Log::warning('Employee credentials email skipped: no valid email on user or employee record', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);

            return false;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Employee credentials email skipped: invalid email format', [
                'user_id' => $user->id,
                'email' => $email,
            ]);

            return false;
        }

        $employeeName = trim($user->first_name.' '.$user->last_name);
        $loginUrl = route('login');
        $registeredEmail = strtolower(trim((string) ($details['registered_email'] ?? $email)));

        try {
            Mail::send(
                'emails.employee_credentials',
                [
                    'employeeName' => $employeeName,
                    'registeredEmail' => $registeredEmail,
                    'companyName' => $details['company_name'] ?? '—',
                    'departmentName' => $details['department_name'] ?? '—',
                    'designationName' => $details['designation_name'] ?? '—',
                    'officeShiftName' => $details['office_shift_name'] ?? '—',
                    'username' => (string) $user->username,
                    'password' => $plainPassword,
                    'staffId' => $staffId,
                    'loginUrl' => $loginUrl,
                ],
                function ($message) use ($email) {
                    $fromAddress = config('mail.from.address');
                    $fromName = config('mail.from.name');
                    $message->from($fromAddress, $fromName)
                        ->replyTo($fromAddress, $fromName)
                        ->to($email)
                        ->subject(__('Your HRMS account has been registered'));
                }
            );

            Log::info('Employee credentials email handed off to SMTP', [
                'email' => $email,
                'user_id' => $user->id,
                'mail_host' => config('mail.host'),
                'mail_from' => config('mail.from.address'),
            ]);

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
