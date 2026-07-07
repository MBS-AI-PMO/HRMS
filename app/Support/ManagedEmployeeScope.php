<?php

namespace App\Support;

use App\Models\location;
use App\Models\Team;

class ManagedEmployeeScope
{
    public static function usesScopedEmployeeList(int $userId, int $roleUsersId): bool
    {
        if ($roleUsersId === 1) {
            return false;
        }

        return Team::userCanAccessEmployeeList($userId)
            || location::userCanAccessLocationEmployeeList($userId);
    }

    public static function managedEmployeeIds(int $userId): array
    {
        $ids = [];

        if (Team::userCanAccessEmployeeList($userId)) {
            $ids = array_merge($ids, Team::memberEmployeeIdsLedByUser($userId));
        }

        if (location::userCanAccessLocationEmployeeList($userId)) {
            $ids = array_merge($ids, location::employeeIdsAtLocationsHeadedByUser($userId));
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public static function canManageLeaveRequests(int $userId): bool
    {
        return Team::userCanManageTeamLeaveRequests($userId)
            || location::userCanManageLocationLeaveRequests($userId);
    }

    public static function canAccessScopedEmployeeList(int $userId, int $roleUsersId): bool
    {
        $user = \App\Models\User::query()->find($userId);

        if (! $user || ! $user->can('scoped-view-employees')) {
            return false;
        }

        return static::usesScopedEmployeeList($userId, $roleUsersId);
    }

    public static function canManageScopedLeave(int $userId): bool
    {
        $user = \App\Models\User::query()->find($userId);

        if (! $user || ! $user->can('scoped-manage-leave')) {
            return false;
        }

        return static::canManageLeaveRequests($userId);
    }

    public static function canAccessMyLocations(int $userId): bool
    {
        $user = \App\Models\User::query()->find($userId);

        if (! $user || ! $user->can('view-my-locations')) {
            return false;
        }

        return location::userCanAccessMyLocationsPage($userId);
    }

    public static function canAccessClockInLocationReport(int $userId, int $roleUsersId): bool
    {
        $user = \App\Models\User::query()->find($userId);

        if (! $user) {
            return false;
        }

        if ($user->can('report-employee')) {
            return true;
        }

        if (! $user->can('report-clock-in-locations')) {
            return false;
        }

        return static::usesScopedEmployeeList($userId, $roleUsersId);
    }

    public static function canAccessMyTeam(int $userId): bool
    {
        $user = \App\Models\User::query()->find($userId);

        if (! $user || ! $user->can('view-my-team')) {
            return false;
        }

        return Team::userHasTeamAccess($userId);
    }

    public static function canViewScopedEmployeeDetails(int $userId, int $roleUsersId): bool
    {
        $user = \App\Models\User::query()->find($userId);

        if (! $user || ! $user->can('scoped-view-employee-details')) {
            return false;
        }

        return static::canAccessScopedEmployeeList($userId, $roleUsersId);
    }
}
