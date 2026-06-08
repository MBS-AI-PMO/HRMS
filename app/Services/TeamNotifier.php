<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Notifications\TeamManagementNotification;

class TeamNotifier
{
    public static function notify(Team $team, string $event, array $extraMemberIds = []): void
    {
        $team->loadMissing(['company', 'departmentHeads', 'projectManager', 'assistantHr', 'members']);

        $companyId = (int) $team->company_id;
        $teamName = $team->team_name;
        $link = route('teams.my');

        if ($event === 'created') {
            $message = __('Team ":name" has been created.', ['name' => $teamName]);
        } elseif ($event === 'updated') {
            $message = __('Team ":name" has been updated.', ['name' => $teamName]);
        } elseif ($event === 'members_assigned') {
            $message = __('Team members have been assigned to ":name".', ['name' => $teamName]);
        } elseif ($event === 'deleted') {
            $message = __('Team ":name" has been removed.', ['name' => $teamName]);
        } else {
            $message = __('Team ":name" was changed.', ['name' => $teamName]);
        }

        $recipients = NotificationRecipientResolver::uniqueUsers(
            NotificationRecipientResolver::usersWithPermissionInCompany('view-team', $companyId),
            static::usersFromEmployeeIds($team->leaderEmployeeIds(), $companyId),
            static::usersFromEmployeeIds($team->members->pluck('id')->all(), $companyId),
            static::usersFromEmployeeIds($extraMemberIds, $companyId),
        );

        foreach ($recipients as $recipient) {
            $recipient->notify(new TeamManagementNotification($message, $link));
        }
    }

    protected static function usersFromEmployeeIds(array $employeeIds, int $companyId)
    {
        $ids = collect($employeeIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $users = User::query()->whereIn('id', $ids)->get();

        return NotificationRecipientResolver::filterByCompany($users, $companyId);
    }

    public static function notifyMemberAssigned(Team $team, array $memberIds): void
    {
        static::notify($team, 'members_assigned', $memberIds);
    }
}
