<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Team;
use App\Models\User;
use App\Scopes\AuthCompanyScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationRecipientResolver
{
    public static function usersWithPermissionInCompany(string $permissionName, int $companyId): Collection
    {
        $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');

        if (! $permissionId) {
            return collect();
        }

        $roleIds = DB::table('role_has_permissions')
            ->where('permission_id', $permissionId)
            ->pluck('role_id');

        if ($roleIds->isEmpty()) {
            return collect();
        }

        $users = User::query()->whereIn('role_users_id', $roleIds)->get();

        return static::filterByCompany($users, $companyId);
    }

    public static function filterByCompany(Collection $users, int $companyId): Collection
    {
        return $users->filter(function (User $user) use ($companyId) {
            $employee = Employee::withoutGlobalScope(AuthCompanyScope::class)->find($user->id);

            return $employee && (int) $employee->company_id === $companyId;
        })->values();
    }

    public static function teamLeadersForEmployee(int $employeeId, ?int $companyId = null): Collection
    {
        $teamsQuery = Team::query()->whereHas('members', function ($query) use ($employeeId) {
            $query->where('employees.id', $employeeId);
        });

        if ($companyId) {
            $teamsQuery->where('company_id', $companyId);
        }

        $leaderIds = collect();

        foreach ($teamsQuery->with(['departmentHeads:id'])->get() as $team) {
            foreach ($team->leaderEmployeeIds() as $leaderId) {
                $leaderIds->push((int) $leaderId);
            }
        }

        if ($leaderIds->isEmpty()) {
            return collect();
        }

        $users = User::query()->whereIn('id', $leaderIds->unique()->values())->get();

        return $companyId ? static::filterByCompany($users, $companyId) : $users;
    }

    /**
     * Project manager + team department heads only (no assistant HR, no team members).
     */
    public static function teamPmAndDepartmentHeadsForEmployee(int $employeeId, ?int $companyId = null): Collection
    {
        $teamsQuery = Team::query()->whereHas('members', function ($query) use ($employeeId) {
            $query->where('employees.id', $employeeId);
        });

        if ($companyId) {
            $teamsQuery->where('company_id', $companyId);
        }

        $approverIds = collect();

        foreach ($teamsQuery->with(['departmentHeads:id'])->get() as $team) {
            foreach ($team->departmentHeads as $head) {
                $approverIds->push((int) $head->id);
            }

            if ($team->project_manager_id) {
                $approverIds->push((int) $team->project_manager_id);
            }
        }

        if ($approverIds->isEmpty()) {
            return collect();
        }

        $users = User::query()->whereIn('id', $approverIds->unique()->values())->get();

        return $companyId ? static::filterByCompany($users, $companyId) : $users;
    }

    public static function leaveWfhEmailRecipients(int $employeeId, int $companyId): Collection
    {
        return static::uniqueUsers(
            static::usersWithPermissionInCompany('view-leave', $companyId),
            static::teamPmAndDepartmentHeadsForEmployee($employeeId, $companyId),
        );
    }

    public static function leaveWfhInAppRecipients(int $employeeId, int $companyId): Collection
    {
        $employee = User::find($employeeId);

        return static::uniqueUsers(
            static::usersWithPermissionInCompany('view-leave', $companyId),
            static::teamLeadersForEmployee($employeeId, $companyId),
            $employee ? collect([$employee]) : collect(),
        );
    }

    public static function uniqueUsers(Collection ...$groups): Collection
    {
        return collect($groups)
            ->flatten()
            ->filter()
            ->unique('id')
            ->values();
    }
}
