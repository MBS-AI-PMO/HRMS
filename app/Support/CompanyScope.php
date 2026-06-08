<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\company;
use App\Scopes\AuthCompanyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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

    public static function employeesForCompany(int $companyId)
    {
        return Employee::query()
            ->select('id', 'first_name', 'last_name')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->whereNull('exit_date')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($employee) => [
                'id' => $employee->id,
                'name' => $employee->full_name,
            ]);
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
}
