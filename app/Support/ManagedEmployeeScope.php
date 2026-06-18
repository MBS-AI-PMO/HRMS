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
}
