<?php

namespace App\Services;

use App\Models\department;
use App\Models\Employee;
use App\Models\leave;
use App\Models\location;
use App\Models\Team;
use App\Models\User;
use App\Scopes\AuthCompanyLocationScope;
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

    public static function departmentHeadForEmployee(int $employeeId, ?int $companyId = null): Collection
    {
        $employee = Employee::withoutGlobalScope(AuthCompanyScope::class)
            ->with('department.DepartmentHead:id')
            ->find($employeeId);

        $headId = (int) ($employee?->department?->DepartmentHead?->id ?? 0);

        if ($headId < 1) {
            return collect();
        }

        $users = User::query()->where('id', $headId)->get();

        return $companyId ? static::filterByCompany($users, $companyId) : $users;
    }

    public static function locationHeadsForEmployee(int $employeeId): Collection
    {
        $employee = Employee::withoutGlobalScope(AuthCompanyScope::class)->find($employeeId);

        if (! $employee || ! $employee->location_id) {
            return collect();
        }

        $location = location::withoutGlobalScope(AuthCompanyLocationScope::class)
            ->with('locationHeads:id')
            ->find((int) $employee->location_id);

        if (! $location) {
            return collect();
        }

        $headIds = collect([(int) $location->location_head])
            ->merge($location->locationHeads->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($headIds->isEmpty()) {
            return collect();
        }

        return User::query()->whereIn('id', $headIds)->get();
    }

    public static function leaveWfhEmailRecipients(int $employeeId, int $companyId, ?string $event = null): Collection
    {
        $groups = [
            static::usersWithPermissionInCompany('view-leave', $companyId),
            static::teamPmAndDepartmentHeadsForEmployee($employeeId, $companyId),
            static::departmentHeadForEmployee($employeeId, $companyId),
            static::locationHeadsForEmployee($employeeId),
        ];

        return static::uniqueUsers(...$groups)
            ->reject(fn (User $user) => (int) $user->id === $employeeId)
            ->values();
    }

    public static function employeeAccountForNotifications(int $employeeId): ?User
    {
        $user = User::query()->find($employeeId);

        if (! $user) {
            return null;
        }

        $resolvedEmail = static::resolveUserEmailAddress($employeeId);

        if ($resolvedEmail !== null) {
            $user->email = $resolvedEmail;
        }

        return $user;
    }

    /**
     * Prefer users.email (login account). Fall back to employees.email when the user row is empty
     * and optionally sync that address back to users so login and mail stay aligned.
     */
    public static function resolveUserEmailAddress(int $userId, ?leave $leave = null, bool $syncToUser = true): ?string
    {
        $user = User::query()->find($userId);

        if (! $user) {
            return null;
        }

        $userEmail = strtolower(trim((string) ($user->email ?? '')));

        if (static::isValidEmail($userEmail)) {
            return $userEmail;
        }

        $employee = Employee::withoutGlobalScope(AuthCompanyScope::class)->find($userId);
        $employeeEmail = strtolower(trim((string) ($employee->email ?? '')));

        if (! static::isValidEmail($employeeEmail)) {
            return null;
        }

        if ($syncToUser) {
            static::syncEmailToUserAccount($userId, $employeeEmail);
        }

        return $employeeEmail;
    }

    public static function emailSourceForUser(int $userId): ?string
    {
        $user = User::query()->find($userId);

        if (! $user) {
            return null;
        }

        $userEmail = strtolower(trim((string) ($user->email ?? '')));

        if (static::isValidEmail($userEmail)) {
            return 'users';
        }

        $employee = Employee::withoutGlobalScope(AuthCompanyScope::class)->find($userId);
        $employeeEmail = strtolower(trim((string) ($employee->email ?? '')));

        return static::isValidEmail($employeeEmail) ? 'employees' : null;
    }

    public static function syncEmailToUserAccount(int $userId, string $email): void
    {
        $email = strtolower(trim($email));

        if (! static::isValidEmail($email)) {
            return;
        }

        User::query()
            ->where('id', $userId)
            ->where(function ($query) {
                $query->whereNull('email')->orWhere('email', '');
            })
            ->update(['email' => $email]);
    }

    /**
     * @return array{
     *     user_email: ?string,
     *     employee_record_email: ?string,
     *     leave_employee_email: ?string,
     *     resolved_email: ?string
     * }
     */
    public static function describeUserEmailSources(int $userId, ?leave $leave = null): array
    {
        $user = User::query()->find($userId);
        $userEmail = strtolower(trim((string) ($user->email ?? '')));
        $employee = Employee::withoutGlobalScope(AuthCompanyScope::class)->find($userId);
        $employeeRecordEmail = strtolower(trim((string) ($employee->email ?? '')));
        $leaveEmployeeEmail = '';

        if ($leave !== null && (int) $leave->employee_id === $userId) {
            $leave->loadMissing('employee');
            $leaveEmployeeEmail = strtolower(trim((string) (optional($leave->employee)->email ?? '')));
        }

        return [
            'user_email' => $userEmail !== '' ? $userEmail : null,
            'employee_record_email' => $employeeRecordEmail !== '' ? $employeeRecordEmail : null,
            'leave_employee_email' => $leaveEmployeeEmail !== '' ? $leaveEmployeeEmail : null,
            'resolved_email' => static::resolveUserEmailAddress($userId, $leave, false),
            'email_source' => static::emailSourceForUser($userId) ?? 'none',
        ];
    }

    public static function hasValidEmail(User $user): bool
    {
        return static::resolveUserEmailAddress((int) $user->id) !== null;
    }

    public static function isValidEmail(?string $email): bool
    {
        return filter_var((string) $email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function detectLikelyEmailTypo(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        if ($email === '') {
            return null;
        }

        if (preg_match('/\.(gmsil|gmial|gamil|gnail)\./', $email) || preg_match('/@(gmsil|gmial|gamil|gnail)\./', $email)) {
            return 'Possible gmail.com typo in email address.';
        }

        if (preg_match('/\.com@gmail\.com$/', $email)) {
            return 'Email looks wrong: ".com@gmail.com" — domain may have been typed twice (e.g. use name@gmail.com not namegmail.com@gmail.com).';
        }

        return null;
    }

    public static function leaveWfhInAppRecipients(int $employeeId, int $companyId): Collection
    {
        $employee = static::employeeAccountForNotifications($employeeId);

        return static::uniqueUsers(
            static::usersWithPermissionInCompany('view-leave', $companyId),
            static::teamLeadersForEmployee($employeeId, $companyId),
            static::departmentHeadForEmployee($employeeId, $companyId),
            static::locationHeadsForEmployee($employeeId),
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
