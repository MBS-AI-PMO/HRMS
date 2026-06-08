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

        foreach ($teamsQuery->get() as $team) {
            if ($team->project_manager_id) {
                $leaderIds->push((int) $team->project_manager_id);
            }
            if ($team->assistant_hr_id) {
                $leaderIds->push((int) $team->assistant_hr_id);
            }
        }

        if ($leaderIds->isEmpty()) {
            return collect();
        }

        $users = User::query()->whereIn('id', $leaderIds->unique()->values())->get();

        return $companyId ? static::filterByCompany($users, $companyId) : $users;
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
