<?php

namespace App\Scopes;

use App\Support\CompanyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AuthCompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! CompanyScope::applies()) {
            return;
        }

        $companyId = CompanyScope::companyId();
        $table = $model->getTable();

        if ($companyId) {
            $builder->where($table.'.company_id', $companyId);

            return;
        }

        $builder->whereRaw('1 = 0');
    }
}
