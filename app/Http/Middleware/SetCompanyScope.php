<?php

namespace App\Http\Middleware;

use App\Support\CompanyScope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetCompanyScope
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && CompanyScope::applies()) {
            $companyId = CompanyScope::companyId();

            if ($companyId) {
                if ($request->has('company_id')) {
                    $request->merge(['company_id' => $companyId]);
                }

                if ($request->has('filter_company')) {
                    $request->merge(['filter_company' => $companyId]);
                }
            }
        }

        return $next($request);
    }
}
