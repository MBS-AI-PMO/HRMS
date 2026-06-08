<?php

namespace App\Services;

use App\Models\leave;
use App\Models\User;
use App\Notifications\LeaveEventNotification;
use App\Notifications\LeaveRequestNotification;
use App\Notifications\WfhEventNotification;
use App\Notifications\WfhRequestNotificationToApprover;

class LeaveNotifier
{
    public static function notify(leave $leave, string $event): void
    {
        $leave->loadMissing('employee', 'LeaveType');

        $companyId = (int) $leave->company_id;
        $employeeId = (int) $leave->employee_id;
        $adminLink = route('leaves.index');
        $leaveTypeName = $leave->LeaveType->leave_type ?? __('Leave');

        if ($event === 'requested') {
            $subject = __('Leave request submitted');
            $message = __('A new leave request has been submitted.');
        } elseif ($event === 'approved') {
            $subject = __('Leave request approved');
            $message = __('Leave request has been approved.');
        } elseif ($event === 'rejected') {
            $subject = __('Leave request rejected');
            $message = __('Leave request has been rejected.');
        } else {
            $subject = __('Leave request updated');
            $message = __('Leave request status is pending.');
        }

        $requestorName = optional($leave->employee)->full_name ?? __('Employee');
        $dateRange = trim(($leave->start_date ?? '') . ' - ' . ($leave->end_date ?? ''));
        $eventMessage = $message . ' (' . $requestorName . ' — ' . $leaveTypeName . ', ' . $dateRange . ')';

        $inAppRecipients = NotificationRecipientResolver::leaveWfhInAppRecipients($employeeId, $companyId);
        $emailRecipients = NotificationRecipientResolver::leaveWfhEmailRecipients($employeeId, $companyId);

        foreach ($inAppRecipients as $recipient) {
            $recipientLink = (int) $recipient->id === $employeeId
                ? route('profile') . '#Leave'
                : $adminLink;

            $recipient->notify(new LeaveRequestNotification($eventMessage, $recipientLink));
        }

        foreach ($emailRecipients as $recipient) {
            $recipientLink = (int) $recipient->id === $employeeId
                ? route('profile') . '#Leave'
                : $adminLink;

            try {
                if (! empty($recipient->email)) {
                    $recipient->notify(new LeaveEventNotification($subject, $eventMessage, $recipientLink));
                }
            } catch (\Throwable $e) {
                // Keep in-app notification even if mail transport fails.
            }
        }
    }

    public static function notifyWfh(leave $leave, string $event): void
    {
        $leave->loadMissing('employee');

        $companyId = (int) $leave->company_id;
        $employeeId = (int) $leave->employee_id;
        $link = route('leaves.index', ['wfh' => 1]);

        if ($event === 'requested') {
            $subject = __('WFH request submitted');
            $message = __('A new WFH request has been submitted.');
        } elseif ($event === 'approved') {
            $subject = __('WFH request approved');
            $message = __('WFH request has been approved.');
        } elseif ($event === 'rejected') {
            $subject = __('WFH request rejected');
            $message = __('WFH request has been rejected.');
        } else {
            $subject = __('WFH request updated');
            $message = __('WFH request status is pending.');
        }

        $requestorName = optional($leave->employee)->full_name ?? __('Employee');
        $eventMessage = $message . ' (' . $requestorName . ')';

        $inAppRecipients = NotificationRecipientResolver::leaveWfhInAppRecipients($employeeId, $companyId);
        $emailRecipients = NotificationRecipientResolver::leaveWfhEmailRecipients($employeeId, $companyId);

        foreach ($inAppRecipients as $recipient) {
            $recipientLink = (int) $recipient->id === $employeeId
                ? route('profile') . '#WFH'
                : $link;

            $recipient->notify(new WfhRequestNotificationToApprover($eventMessage, $recipientLink));
        }

        foreach ($emailRecipients as $recipient) {
            $recipientLink = (int) $recipient->id === $employeeId
                ? route('profile') . '#WFH'
                : $link;

            try {
                if (! empty($recipient->email)) {
                    $recipient->notify(new WfhEventNotification($subject, $eventMessage, $recipientLink));
                }
            } catch (\Throwable $e) {
                // Keep in-app notification even if mail transport fails.
            }
        }
    }
}
