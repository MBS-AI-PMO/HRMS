<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\company;
use App\Models\location;
use App\Scopes\AuthCompanyScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CompanyScope
{
    protected static ?bool $applies = null;

    protected static ?int $companyId = null;

    protected static bool $resolved = false;

    /**
     * Non-admin users (e.g. HR) are limited to their own company.
     */
    public static function applies(): bool
    {
        if (static::$applies !== null) {
            return static::$applies;
        }

        $user = Auth::user();

        static::$applies = $user !== null && (int) $user->role_users_id !== 1;

        return static::$applies;
    }

    public static function companyId(): ?int
    {
        if (static::$resolved) {
            return static::$companyId;
        }

        static::$resolved = true;

        if (! static::applies()) {
            static::$companyId = null;

            return null;
        }

        $user = Auth::user();
        $employee = Employee::withoutGlobalScope(AuthCompanyScope::class)->find($user->id);
        static::$companyId = $employee?->company_id ? (int) $employee->company_id : null;

        return static::$companyId;
    }

    public static function clientCompaniesForSelect()
    {
        $query = company::select('id', 'company_name')
            ->whereRaw('LOWER(company_name) NOT LIKE ?', ['%solochoice%'])
            ->orderBy('company_name');

        if (static::applies()) {
            $companyId = static::companyId();

            if (! $companyId) {
                return collect();
            }

            $query->where('id', $companyId);
        }

        return $query->get();
    }

    public static function solochoicezCompanyId(): ?int
    {
        $id = company::withoutGlobalScopes()
            ->whereRaw('LOWER(company_name) LIKE ?', ['%solochoice%'])
            ->value('id');

        return $id ? (int) $id : null;
    }

    protected static function normalizeCompanyName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[.\-_]+/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    public static function solochoicezEmployeesForSelect()
    {
        $companyId = static::solochoicezCompanyId();

        if (! $companyId) {
            return collect();
        }

        return Employee::withoutGlobalScope(AuthCompanyScope::class)
            ->select('id', 'first_name', 'last_name')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->whereNull('exit_date')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public static function companiesForSelect()
    {
        $query = company::select('id', 'company_name')->orderBy('company_name');

        if (static::applies()) {
            $companyId = static::companyId();

            if (! $companyId) {
                return collect();
            }

            $query->where('id', $companyId);
        }

        return $query->get();
    }

    /**
     * All companies linked to locations headed by this user (cross-company location heads).
     */
    public static function companiesForLocationHead(int $userId)
    {
        $locationIds = location::locationIdsHeadedByUser($userId);

        if ($locationIds === []) {
            return collect();
        }

        $companyIds = DB::table('company_location')
            ->whereIn('location_id', $locationIds)
            ->pluck('company_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($companyIds === []) {
            return collect();
        }

        return company::withoutGlobalScopes()
            ->select('id', 'company_name')
            ->whereIn('id', $companyIds)
            ->orderBy('company_name')
            ->get();
    }

    /**
     * Companies linked to the given location IDs (for scoped location managers).
     */
    public static function companiesForLocationIds(array $locationIds)
    {
        $locationIds = array_values(array_filter(array_map('intval', $locationIds)));

        if ($locationIds === []) {
            return collect();
        }

        $companyIds = DB::table('company_location')
            ->whereIn('location_id', $locationIds)
            ->pluck('company_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($companyIds === []) {
            return collect();
        }

        return company::withoutGlobalScopes()
            ->select('id', 'company_name')
            ->whereIn('id', $companyIds)
            ->orderBy('company_name')
            ->get();
    }

    /**
     * Logged-in user's own company (from employees.company_id), for locking team forms.
     */
    public static function teamFormCompany(): ?company
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $employee = Employee::withoutGlobalScope(AuthCompanyScope::class)->find($user->id);
        $companyId = $employee?->company_id ? (int) $employee->company_id : null;

        if (! $companyId) {
            return null;
        }

        return company::select('id', 'company_name')->find($companyId);
    }

    public static function resolveCompanyIdForTeamInput($requested): int
    {
        $lockedCompany = static::teamFormCompany();

        if ($lockedCompany) {
            return (int) $lockedCompany->id;
        }

        return static::resolveCompanyIdForInput($requested);
    }

    public static function employeesForCompany(int $companyId)
    {
        return Employee::withoutGlobalScopes()
            ->select('id', 'first_name', 'last_name')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->where(function ($query) {
                $query->whereNull('exit_date')
                    ->orWhere('exit_date', '>=', date('Y-m-d'))
                    ->orWhere('exit_date', '0000-00-00');
            })
            ->orderBy('first_name')
            ->get()
            ->map(fn ($employee) => [
                'id' => $employee->id,
                'name' => $employee->full_name,
            ]);
    }

    /**
     * Parent company used for departments/designations when employee belongs to a client.
     */
    public static function resolveCompanyIdForClient(int $clientId): ?int
    {
        $columns = ['id', 'company_name'];

        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'parent_company_id')) {
            $columns[] = 'parent_company_id';
        }

        $client = Client::query()
            ->select($columns)
            ->find($clientId);

        if (! $client) {
            return null;
        }

        if (! empty($client->parent_company_id)) {
            return (int) $client->parent_company_id;
        }

        $fromProject = Project::query()
            ->where('client_id', $clientId)
            ->whereNotNull('company_id')
            ->value('company_id');

        if ($fromProject) {
            return (int) $fromProject;
        }

        $organization = static::normalizeCompanyName((string) $client->company_name);

        if ($organization !== '') {
            foreach (company::withoutGlobalScopes()->select('id', 'company_name')->get() as $company) {
                $name = static::normalizeCompanyName((string) $company->company_name);

                if ($name === '') {
                    continue;
                }

                if ($name === $organization || str_contains($organization, $name) || str_contains($name, $organization)) {
                    return (int) $company->id;
                }
            }

            if (str_contains($organization, 'solochoice')) {
                return static::solochoicezCompanyId();
            }
        }

        return null;
    }

    public static function resolveCompanyIdForInput($requested): int
    {
        $scopedId = static::companyId();

        if (static::applies()) {
            if (! $scopedId) {
                abort(403, __('Your account is not linked to a company.'));
            }

            return $scopedId;
        }

        return (int) $requested;
    }

    public static function assertCompanyAccess(?int $companyId): void
    {
        $scopedId = static::companyId();

        if (static::applies() && $scopedId && (int) $companyId !== $scopedId) {
            abort(403, __('You are not authorized to access this company data.'));
        }
    }

    public static function scopeLocations(Builder $query): Builder
    {
        $companyId = static::companyId();

        if (! static::applies() || ! $companyId) {
            return $query;
        }

        return $query->whereHas('companies', function (Builder $builder) use ($companyId) {
            $builder->where('companies.id', $companyId);
        });
    }

    public static function scopeUsers(Builder $query): Builder
    {
        if (! static::applies()) {
            return $query;
        }

        $companyId = static::companyId();

        if (! $companyId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('id', function ($sub) use ($companyId) {
            $sub->select('id')
                ->from('employees')
                ->where('company_id', $companyId);
        });
    }

    /**
     * Client IDs that belong to a company (for filter dropdowns).
     */
    public static function clientIdsForCompany(int $companyId): array
    {
        $companyName = company::query()->whereKey($companyId)->value('company_name');
        $normalizedName = $companyName ? strtolower(trim((string) $companyName)) : '';

        $ids = collect();

        $ids = $ids->merge(
            Project::query()
                ->where('company_id', $companyId)
                ->whereNotNull('client_id')
                ->distinct()
                ->pluck('client_id')
        );

        $ids = $ids->merge(
            Employee::withoutGlobalScope(AuthCompanyScope::class)
                ->where('company_id', $companyId)
                ->whereNotNull('client_id')
                ->distinct()
                ->pluck('client_id')
        );

        $clientQuery = Client::query()->select('id');

        $clientQuery->where(function ($query) use ($companyId, $normalizedName) {
            if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'parent_company_id')) {
                $query->where('parent_company_id', $companyId);
            }

            if ($normalizedName !== '') {
                if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'parent_company_id')) {
                    $query->orWhereRaw('LOWER(TRIM(company_name)) = ?', [$normalizedName]);
                } else {
                    $query->whereRaw('LOWER(TRIM(company_name)) = ?', [$normalizedName]);
                }
            }
        });

        $ids = $ids->merge($clientQuery->pluck('id'));

        return $ids
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
