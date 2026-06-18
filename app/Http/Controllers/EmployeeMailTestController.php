<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Scopes\AuthCompanyScope;
use App\Services\MailSendLogger;
use App\Services\NotificationRecipientResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Throwable;

class EmployeeMailTestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Request $request)
    {
        $targetUser = $this->resolveTargetUser($request);
        $this->assertCanAccessTest($targetUser);

        $diagnostics = $this->buildDiagnostics($targetUser);

        if ($request->expectsJson() || $request->boolean('json')) {
            return response()->json([
                'success' => true,
                'diagnostics' => $diagnostics,
                'send_url' => route('employee.mail-test.send'),
            ]);
        }

        return view('employee.mail_test', [
            'targetUser' => $targetUser,
            'diagnostics' => $diagnostics,
            'isAdmin' => (int) auth()->user()->role_users_id === 1,
        ]);
    }

    public function send(Request $request)
    {
        $targetUser = $this->resolveTargetUser($request);
        $this->assertCanAccessTest($targetUser);

        $diagnostics = $this->buildDiagnostics($targetUser);
        $email = $diagnostics['resolved_email'];

        if ($email === null) {
            return $this->respond($request, false, __('No valid email found for this employee account.'), $diagnostics);
        }

        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');
        $sentAt = now()->toDateTimeString();
        $recipientName = trim($targetUser->first_name.' '.$targetUser->last_name);
        $role = Role::find($targetUser->role_users_id);
        $roleName = $role->name ?? __('Employee');

        $body = implode("\n", [
            __('Hello').' '.($recipientName !== '' ? $recipientName : __('Employee')).',',
            '',
            __('This is an employee-role email delivery test from HRMS.'),
            '',
            __('Sent at').': '.$sentAt,
            __('Role').': '.$roleName,
            __('User ID').': '.$targetUser->id,
            __('Email used').': '.$email,
            __('Email source').': '.($diagnostics['email_source'] ?? '—'),
            __('SMTP host').': '.config('mail.host'),
            __('Mail from').': '.$fromAddress,
            '',
            __('If you received this email, employee notifications should work.'),
            __('Dashboard').': '.route('employee.EmployeeDashboard'),
        ]);

        $deliveryHint = MailSendLogger::recipientDomainHint($email);

        try {
            $mailLogs = (new MailSendLogger)->wrap(
                'Employee role mail test',
                [
                    'user_id' => $targetUser->id,
                    'role_users_id' => $targetUser->role_users_id,
                    'role_name' => $roleName,
                    'email' => $email,
                    'email_source' => $diagnostics['email_source'],
                    'tested_by' => auth()->id(),
                    'subject' => __('HRMS employee email test').' - '.$sentAt,
                ],
                function () use ($body, $email, $fromAddress, $fromName, $sentAt) {
                    Mail::raw($body, function ($message) use ($email, $fromAddress, $fromName, $sentAt) {
                        $message->from($fromAddress, $fromName)
                            ->replyTo($fromAddress, $fromName)
                            ->to($email)
                            ->subject(__('HRMS employee email test').' - '.$sentAt);
                    });
                }
            );

            $diagnostics['mail_logs'] = $mailLogs;
            $diagnostics['delivery_hint'] = $deliveryHint;
            $diagnostics['log_stages'] = ['BEFORE_MAIL', 'AFTER_MAIL'];

            $successMessage = __('SMTP accepted test email for :email. Check inbox, spam, and promotions.', ['email' => $email]);

            if ($deliveryHint) {
                $successMessage .= ' '.$deliveryHint;
            }

            return $this->respond($request, true, $successMessage, $diagnostics);
        } catch (Throwable $e) {
            Log::error('[MAIL AFTER] Employee role mail test — FAILED', [
                'stage' => 'AFTER_MAIL_FAILED',
                'user_id' => $targetUser->id,
                'email' => $email,
                'error' => $e->getMessage(),
                'tested_by' => auth()->id(),
            ]);

            return $this->respond($request, false, __('SMTP error: ').$e->getMessage(), $diagnostics);
        }
    }

    protected function resolveTargetUser(Request $request): User
    {
        $authUser = auth()->user();

        if ($request->filled('user_id') && (int) $authUser->role_users_id === 1) {
            return User::query()->findOrFail((int) $request->user_id);
        }

        return $authUser;
    }

    protected function assertCanAccessTest(User $targetUser): void
    {
        $authUser = auth()->user();

        if ((int) $authUser->role_users_id === 1) {
            if (! $this->isEmployeeAccount($targetUser)) {
                abort(403, __('Admin can only test employee-role accounts. Use ?user_id=EMPLOYEE_ID'));
            }

            return;
        }

        if ((int) $authUser->id !== (int) $targetUser->id) {
            abort(403, __('You can only test your own email.'));
        }

        if (! $this->isEmployeeAccount($authUser)) {
            abort(403, __('This test route is only for employee role accounts.'));
        }
    }

    protected function isEmployeeAccount(User $user): bool
    {
        if ((int) $user->role_users_id === 1) {
            return false;
        }

        $role = Role::find($user->role_users_id);
        $roleName = strtolower((string) ($role->name ?? ''));

        if (str_contains($roleName, 'client')) {
            return false;
        }

        return str_contains($roleName, 'employee')
            || str_contains($roleName, 'staff')
            || (int) $user->role_users_id === 2;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildDiagnostics(User $user): array
    {
        $employee = Employee::withoutGlobalScope(AuthCompanyScope::class)->find($user->id);
        $sources = NotificationRecipientResolver::describeUserEmailSources((int) $user->id);
        $role = Role::find($user->role_users_id);
        $typo = NotificationRecipientResolver::detectLikelyEmailTypo(
            $sources['employee_record_email'] ?? $sources['user_email']
        );

        return array_merge($sources, [
            'user_id' => (int) $user->id,
            'role_users_id' => (int) $user->role_users_id,
            'role_name' => $role->name ?? '—',
            'employee_name' => $employee?->full_name ?? trim($user->first_name.' '.$user->last_name),
            'mail_driver' => config('mail.default'),
            'mail_host' => config('mail.mailers.smtp.host'),
            'mail_port' => config('mail.mailers.smtp.port'),
            'mail_encryption' => config('mail.mailers.smtp.encryption'),
            'mail_from' => config('mail.from.address'),
            'mail_username' => config('mail.mailers.smtp.username'),
            'delivery_hint' => MailSendLogger::recipientDomainHint((string) ($sources['resolved_email'] ?? '')),
            'typo_hint' => $typo,
            'can_send' => $sources['resolved_email'] !== null,
        ]);
    }

    protected function respond(Request $request, bool $success, string $message, array $diagnostics)
    {
        if ($request->expectsJson() || $request->boolean('json')) {
            return response()->json([
                'success' => $success,
                'message' => $message,
                'diagnostics' => $diagnostics,
            ], $success ? 200 : 422);
        }

        return redirect()
            ->route('employee.mail-test', $request->only('user_id'))
            ->with($success ? 'success' : 'error', $message)
            ->with('mail_test_diagnostics', $diagnostics);
    }
}
