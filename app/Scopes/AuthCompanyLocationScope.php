<?php

namespace App\Scopes;

use App\Support\CompanyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AuthCompanyLocationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! CompanyScope::applies()) {
            return;
        }

        $companyId = CompanyScope::companyId();

        if (! $companyId) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->whereHas('companies', function (Builder $query) use ($companyId) {
            $query->where('companies.id', $companyId);
        });
    }
}
