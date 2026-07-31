<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
use App\Support\ClientDisplay;
use App\Support\CompanyScope;

class ManagedEmployeeScope
{
    public static function usesScopedEmployeeList(int $userId, int $roleUsersId): bool
    {
        if ($roleUsersId === 1) {
            return false;
        }

        return Project::userLeadsAnyProject($userId)
            || Location::userCanAccessLocationEmployeeList($userId);
    }

    public static function managedEmployeeIds(int $userId): array
    {
        $ids = [];

        if (Project::userLeadsAnyProject($userId)) {
            $ids = array_merge($ids, Project::memberEmployeeIdsLedBy($userId));
        }

        if (Location::userCanAccessLocationEmployeeList($userId)) {
            $ids = array_merge($ids, Location::employeeIdsAtLocationsHeadedByUser($userId));
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public static function canManageLeaveRequests(int $userId): bool
    {
        return Project::userLeadsAnyProject($userId)
            || Location::userCanManageLocationLeaveRequests($userId);
    }

    public static function canAccessScopedEmployeeList(int $userId, int $roleUsersId): bool
    {
        if ($roleUsersId === 1) {
            return false;
        }

        // Project leads automatically get a scoped list of their project members.
        if (Project::userLeadsAnyProject($userId)) {
            return true;
        }

        $user = \App\Models\User::query()->find($userId);

        if (! $user || ! $user->can('scoped-view-employees')) {
            return false;
        }

        return Location::userCanAccessLocationEmployeeList($userId);
    }

    public static function canManageScopedLeave(int $userId): bool
    {
        // Project leads automatically manage leave/WFH of their project members.
        if (Project::userLeadsAnyProject($userId)) {
            return true;
        }

        $user = \App\Models\User::query()->find($userId);

        if (! $user || ! $user->can('scoped-manage-leave')) {
            return false;
        }

        return Location::userCanManageLocationLeaveRequests($userId);
    }

    public static function canAccessMyLocations(int $userId): bool
    {
        $user = \App\Models\User::query()->find($userId);

        if (! $user || ! $user->can('view-my-locations')) {
            return false;
        }

        return Location::userCanAccessMyLocationsPage($userId);
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
        // Being a project lead is enough to access the "My Team" page.
        return Project::userLeadsAnyProject($userId);
    }

    public static function canViewScopedEmployeeDetails(int $userId, int $roleUsersId): bool
    {
        if (Project::userLeadsAnyProject($userId)) {
            return true;
        }

        $user = \App\Models\User::query()->find($userId);

        if (! $user || ! $user->can('scoped-view-employee-details')) {
            return false;
        }

        return static::canAccessScopedEmployeeList($userId, $roleUsersId);
    }

    /**
     * Project leads get limited attendance filters: their company/client pre-selected
     * and locked (readonly) when unique.
     *
     * @return array{
     *     locked: bool,
     *     lock_company: bool,
     *     lock_client: bool,
     *     company_id: ?int,
     *     client_id: ?int,
     *     companies: \Illuminate\Support\Collection,
     *     clients: \Illuminate\Support\Collection
     * }
     */
    public static function projectLeadAttendanceFilterLock(int $userId): array
    {
        $empty = [
            'locked' => false,
            'lock_company' => false,
            'lock_client' => false,
            'company_id' => null,
            'client_id' => null,
            'companies' => collect(),
            'clients' => collect(),
        ];

        if (! Project::userLeadsAnyProject($userId)) {
            return $empty;
        }

        $projectIds = Project::projectIdsLedBy($userId);
        $projects = Project::query()
            ->whereIn('id', $projectIds ?: [0])
            ->get(['id', 'company_id', 'client_id']);

        $companyIds = $projects->pluck('company_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $clientIds = $projects->pluck('client_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($clientIds as $clientId) {
            $resolved = CompanyScope::resolveCompanyIdForClient((int) $clientId);
            if ($resolved) {
                $companyIds->push((int) $resolved);
            }
        }
        $companyIds = $companyIds->unique()->values();

        $employee = Employee::withoutGlobalScopes()
            ->select('id', 'company_id', 'client_id')
            ->find($userId);

        if ($employee?->company_id) {
            $companyIds->push((int) $employee->company_id);
            $companyIds = $companyIds->unique()->values();
        }

        if ($employee?->client_id) {
            $clientIds->push((int) $employee->client_id);
            $clientIds = $clientIds->unique()->values();
        }

        $companyId = $companyIds->count() === 1
            ? (int) $companyIds->first()
            : ($employee?->company_id ? (int) $employee->company_id : ($companyIds->first() ? (int) $companyIds->first() : null));

        if (! $companyId && $employee?->client_id) {
            $companyId = CompanyScope::resolveCompanyIdForClient((int) $employee->client_id);
        }

        $clientId = $clientIds->count() === 1
            ? (int) $clientIds->first()
            : ($employee?->client_id ? (int) $employee->client_id : ($clientIds->first() ? (int) $clientIds->first() : null));

        $companies = $companyIds->isNotEmpty()
            ? Company::query()->whereIn('id', $companyIds)->select('id', 'company_name')->orderBy('company_name')->get()
            : ($companyId
                ? Company::query()->whereKey($companyId)->select('id', 'company_name')->get()
                : collect());

        $clients = $clientIds->isNotEmpty()
            ? Client::query()->whereIn('id', $clientIds)->select('id', 'company_name', 'first_name', 'last_name')->orderBy('company_name')->get()
            : ($clientId
                ? Client::query()->whereKey($clientId)->select('id', 'company_name', 'first_name', 'last_name')->get()
                : collect());

        $clients = $clients->map(function ($client) {
            $client->display_name = ClientDisplay::label($client);

            return $client;
        });

        return [
            'locked' => true,
            'lock_company' => $companyId !== null,
            'lock_client' => $clientId !== null,
            'company_id' => $companyId,
            'client_id' => $clientId,
            'companies' => $companies,
            'clients' => $clients,
        ];
    }
}
