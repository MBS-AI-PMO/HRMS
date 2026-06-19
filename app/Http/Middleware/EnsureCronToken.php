<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects maintenance/cron HTTP endpoints from public access.
 * Set CRON_SECRET in .env and call: /update-attendance-type?token=YOUR_SECRET
 */
class EnsureCronToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('app.cron_secret', env('CRON_SECRET', ''));

        if ($secret === '') {
            abort(403, __('This endpoint is disabled.'));
        }

        $token = (string) ($request->query('token') ?? $request->header('X-Cron-Token', ''));

        if (! hash_equals($secret, $token)) {
            abort(403, __('Invalid cron token.'));
        }

        return $next($request);
    }
}
