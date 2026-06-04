<?php

namespace App\Services;

use App\Models\department;
use App\Models\leave;
use App\Models\User;
use App\Notifications\LeaveEventNotification;
use App\Notifications\LeaveRequestNotification;
use Illuminate\Support\Facades\DB;

class LeaveNotifier
{
    public static function notify(leave $leave, string $event): void
    {
        $leave->loadMissing('employee', 'LeaveType');

        $employee = User::find($leave->employee_id);
        $departmentHeadId = department::where('id', $leave->department_id)->value('department_head');
        $departmentHeadUser = $departmentHeadId ? User::find($departmentHeadId) : null;

        $roleIds = DB::table('role_has_permissions')->where('permission_id', 294)->pluck('role_id');
        $roleIds[] = 1;
        $permissionUsers = User::query()->whereIn('role_users_id', $roleIds)->get();

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

        $recipients = collect()
            ->merge($permissionUsers);

        if ($departmentHeadUser) {
            $recipients->push($departmentHeadUser);
        }
        if ($employee) {
            $recipients->push($employee);
        }

        $recipients = $recipients->filter()->unique('id');

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
