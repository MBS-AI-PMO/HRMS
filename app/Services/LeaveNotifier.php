<?php

namespace App\Services;

use App\Models\leave;
use App\Models\User;
use App\Notifications\LeaveRequestNotification;
use App\Notifications\WfhRequestNotificationToApprover;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeaveNotifier
{
    public static function notify(leave $leave, string $event): void
    {
        $leave->loadMissing('employee', 'LeaveType', 'approvedByUser');

        $companyId = (int) $leave->company_id;
        $employeeId = (int) $leave->employee_id;
        $adminLink = route('leaves.index');
        $leaveTypeName = $leave->LeaveType->leave_type ?? __('Leave');

        if ($event === 'requested') {
            $message = __('A new leave request has been submitted.');
        } elseif ($event === 'approved') {
            $message = __('Leave request has been approved.');
        } elseif ($event === 'rejected') {
            $message = __('Leave request has been rejected.');
        } else {
            $message = __('Leave request status is pending.');
        }

        $requestorName = optional($leave->employee)->full_name ?? __('Employee');
        $dateRange = trim(($leave->start_date ?? '').' - '.($leave->end_date ?? ''));
        $eventMessage = $message.' ('.$requestorName.' — '.$leaveTypeName.', '.$dateRange.')';
        $eventMessage .= static::decisionSuffix($leave, $event);

        $inAppRecipients = NotificationRecipientResolver::leaveWfhInAppRecipients($employeeId, $companyId);
        $emailRecipients = NotificationRecipientResolver::leaveWfhEmailRecipients($employeeId, $companyId, $event);

        foreach ($inAppRecipients as $recipient) {
            $recipientLink = (int) $recipient->id === $employeeId
                ? route('profile').'#Leave'
                : $adminLink;

            $recipient->notify(new LeaveRequestNotification($eventMessage, $recipientLink));
        }

        foreach ($emailRecipients as $recipient) {
            static::sendLeaveEmail(
                $recipient,
                $leave,
                $employeeId,
                $event,
                LeaveMailPresenter::build(
                    $leave,
                    $event,
                    $adminLink,
                    false,
                    false
                ),
                'leave',
                false
            );
        }

        static::sendApplicantEmail($leave, $employeeId, $event, 'leave', false);
    }

    public static function notifyWfh(leave $leave, string $event): void
    {
        $leave->loadMissing('employee', 'approvedByUser');

        $companyId = (int) $leave->company_id;
        $employeeId = (int) $leave->employee_id;
        $link = route('leaves.index', ['wfh' => 1]);

        if ($event === 'requested') {
            $message = __('A new WFH request has been submitted.');
        } elseif ($event === 'approved') {
            $message = __('WFH request has been approved.');
        } elseif ($event === 'rejected') {
            $message = __('WFH request has been rejected.');
        } else {
            $message = __('WFH request status is pending.');
        }

        $requestorName = optional($leave->employee)->full_name ?? __('Employee');
        $eventMessage = $message.' ('.$requestorName.')';
        $eventMessage .= static::decisionSuffix($leave, $event);

        $inAppRecipients = NotificationRecipientResolver::leaveWfhInAppRecipients($employeeId, $companyId);
        $emailRecipients = NotificationRecipientResolver::leaveWfhEmailRecipients($employeeId, $companyId, $event);

        foreach ($inAppRecipients as $recipient) {
            $recipientLink = (int) $recipient->id === $employeeId
                ? route('profile').'#WFH'
                : $link;

            $recipient->notify(new WfhRequestNotificationToApprover($eventMessage, $recipientLink));
        }

        foreach ($emailRecipients as $recipient) {
            static::sendLeaveEmail(
                $recipient,
                $leave,
                $employeeId,
                $event,
                LeaveMailPresenter::build(
                    $leave,
                    $event,
                    $link,
                    true,
                    false
                ),
                'wfh',
                false
            );
        }

        static::sendApplicantEmail($leave, $employeeId, $event, 'wfh', true);
    }

    protected static function sendApplicantEmail(leave $leave, int $employeeId, string $event, string $type, bool $isWfh): void
    {
        if (! in_array($event, ['requested', 'approved', 'rejected'], true)) {
            return;
        }

        $employeeUser = NotificationRecipientResolver::employeeAccountForNotifications($employeeId);

        if (! $employeeUser) {
            Log::warning(ucfirst($type).' employee email skipped: no user account', [
                'employee_id' => $employeeId,
                'leave_id' => $leave->id,
                'event' => $event,
                'email_sent_to_employee' => false,
            ]);

            return;
        }

        $recipientLink = route('profile').($isWfh ? '#WFH' : '#Leave');

        static::sendLeaveEmail(
            $employeeUser,
            $leave,
            $employeeId,
            $event,
            LeaveMailPresenter::build(
                $leave,
                $event,
                $recipientLink,
                $isWfh,
                true
            ),
            $type,
            true
        );
    }

    protected static function sendLeaveEmail(
        User $recipient,
        leave $leave,
        int $employeeId,
        string $event,
        array $mailData,
        string $type,
        bool $isEmployeeRecipient = false
    ): string {
        $email = static::resolveRecipientEmail($recipient, $leave, $employeeId);

        if ($email === null) {
            $skipPayload = [
                'user_id' => $recipient->id,
                'employee_id' => $employeeId,
                'leave_id' => $leave->id,
                'event' => $mailData['subject'] ?? '',
            ];

            Log::warning(ucfirst($type).' email skipped: no valid email address', $skipPayload);

            if ($isEmployeeRecipient) {
                $emailSources = NotificationRecipientResolver::describeUserEmailSources($employeeId, $leave);

                $typoHint = NotificationRecipientResolver::detectLikelyEmailTypo(
                    $emailSources['employee_record_email'] ?? $emailSources['user_email']
                );

                Log::warning(ucfirst($type).' employee email NOT sent: no valid email address', array_merge($skipPayload, [
                    'employee_name' => optional($leave->employee)->full_name ?? __('Employee'),
                    'user_email' => $emailSources['user_email'],
                    'employee_record_email' => $emailSources['employee_record_email'],
                    'email_source' => $emailSources['email_source'] ?? 'none',
                    'email_sent_to_employee' => false,
                    'hint' => 'Set a valid email on the employee profile or user account (users + employees tables).',
                    'typo_hint' => $typoHint,
                ]));
            }

            return 'skipped';
        }

        $recipientName = trim(($recipient->first_name ?? '').' '.($recipient->last_name ?? ''));

        $viewData = array_merge($mailData, [
            'recipientName' => $recipientName !== '' ? $recipientName : __('Team Member'),
        ]);
        $plainBody = static::buildPlainTextBody($viewData);

        try {
            Mail::send(
                'emails.leave_event',
                $viewData,
                function ($message) use ($email, $mailData, $plainBody) {
                    $fromAddress = config('mail.from.address');
                    $fromName = config('mail.from.name');

                    $message->from($fromAddress, $fromName)
                        ->replyTo($fromAddress, $fromName)
                        ->to($email)
                        ->subject($mailData['subject'])
                        ->text($plainBody);
                }
            );

            $logPayload = [
                'email' => $email,
                'user_id' => $recipient->id,
                'leave_id' => $leave->id,
                'subject' => $mailData['subject'] ?? '',
                'mail_from' => config('mail.from.address'),
                'mail_host' => config('mail.host'),
                'recipient_role' => $isEmployeeRecipient ? 'employee' : 'approver',
            ];

            Log::info(ucfirst($type).' email sent', $logPayload);

            if ($isEmployeeRecipient) {
                $emailSources = NotificationRecipientResolver::describeUserEmailSources($employeeId, $leave);

                Log::info(ucfirst($type).' employee email SENT', array_merge($logPayload, [
                    'event' => $event,
                    'employee_id' => $employeeId,
                    'employee_name' => optional($leave->employee)->full_name ?? __('Employee'),
                    'user_email' => $emailSources['user_email'],
                    'employee_record_email' => $emailSources['employee_record_email'],
                    'sent_to_email' => $email,
                    'email_source' => $emailSources['email_source'] ?? 'users',
                    'email_sent_to_employee' => true,
                ]));
            }

            return 'sent';
        } catch (\Throwable $e) {
            $errorPayload = [
                'email' => $email,
                'user_id' => $recipient->id,
                'leave_id' => $leave->id,
                'message' => $e->getMessage(),
            ];

            Log::error(ucfirst($type).' email failed', $errorPayload);

            if ($isEmployeeRecipient) {
                Log::error(ucfirst($type).' employee email NOT sent: SMTP error', array_merge($errorPayload, [
                    'employee_id' => $employeeId,
                    'email_sent_to_employee' => false,
                ]));
            }

            return 'failed';
        }
    }

    protected static function buildPlainTextBody(array $mailData): string
    {
        $lines = [
            __('Hello').' '.($mailData['recipientName'] ?? __('Team Member')).',',
            '',
            (string) ($mailData['intro'] ?? ''),
            '',
            (string) ($mailData['headline'] ?? ''),
            __('Employee').': '.($mailData['employeeName'] ?? '—'),
            __('Status').': '.($mailData['statusLabel'] ?? '—'),
            __('Dates').': '.($mailData['startDate'] ?? '—').' - '.($mailData['endDate'] ?? '—'),
            '',
            __('View Request').': '.($mailData['actionUrl'] ?? ''),
            '',
            (string) ($mailData['companyName'] ?? config('app.name')),
        ];

        return implode("\n", array_filter($lines, static fn ($line) => $line !== null));
    }

    protected static function resolveRecipientEmail(User $recipient, leave $leave, int $employeeId): ?string
    {
        return NotificationRecipientResolver::resolveUserEmailAddress((int) $recipient->id);
    }

    protected static function decisionSuffix(leave $leave, string $event): string
    {
        $deciderName = $leave->approvedByName();

        if ($deciderName === '') {
            return '';
        }

        if ($event === 'approved') {
            return ' '.__('Approved by :name.', ['name' => $deciderName]);
        }

        if ($event === 'rejected') {
            return ' '.__('Rejected by :name.', ['name' => $deciderName]);
        }

        return '';
    }
}
