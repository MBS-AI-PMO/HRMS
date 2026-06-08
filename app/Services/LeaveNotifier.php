<?php

namespace App\Services;

use App\Models\department;
use App\Models\leave;
use App\Models\User;
use App\Notifications\LeaveEventNotification;
use App\Notifications\LeaveRequestNotification;

class LeaveNotifier
{
    public static function notify(leave $leave, string $event): void
    {
        $leave->loadMissing('employee', 'LeaveType');

        $companyId = (int) $leave->company_id;
        $employee = User::find($leave->employee_id);
        $departmentHeadId = department::where('id', $leave->department_id)->value('department_head');
        $departmentHeadUser = $departmentHeadId ? User::find($departmentHeadId) : null;

        $permissionUsers = NotificationRecipientResolver::usersWithPermissionInCompany('view-leave', $companyId);
        $teamLeaders = NotificationRecipientResolver::teamLeadersForEmployee((int) $leave->employee_id, $companyId);

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

        $recipients = NotificationRecipientResolver::uniqueUsers(
            $permissionUsers,
            $teamLeaders,
            $departmentHeadUser ? collect([$departmentHeadUser]) : collect(),
            $employee ? collect([$employee]) : collect(),
        );

        $recipients = NotificationRecipientResolver::filterByCompany($recipients, $companyId);

        foreach ($recipients as $recipient) {
            $recipientLink = (int) $recipient->id === (int) $leave->employee_id
                ? route('profile') . '#Leave'
                : $adminLink;

            $recipient->notify(new LeaveRequestNotification($eventMessage, $recipientLink));

            try {
                if (! empty($recipient->email)) {
                    $recipient->notify(new LeaveEventNotification($subject, $eventMessage, $recipientLink));
                }
            } catch (\Throwable $e) {
                // Keep in-app notification even if mail transport fails.
            }
        }
    }
}
