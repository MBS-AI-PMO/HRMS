<?php

namespace App\Http\Middleware;

use App\Models\EmployeeActivityLog;
use Closure;
use Illuminate\Http\Request;

class LogEmployeeActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!auth()->check()) {
            return $response;
        }

        if (!in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        $user = auth()->user();
        $routeName = optional($request->route())->getName() ?? 'unknown.route';

        // Avoid noisy entries for explicit activity-log endpoints.
        if (str_contains($routeName, 'activity_logs')) {
            return $response;
        }

        $payload = $request->except([
            '_token',
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
        ]);

        EmployeeActivityLog::write(
            (int) $user->id,
            (int) $user->id,
            'request.' . strtolower($request->method()),
            'Request performed: ' . $routeName,
            [
                'route' => $routeName,
                'method' => strtoupper($request->method()),
                'path' => $request->path(),
                'payload' => $payload,
                'response_status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
            ],
            $request->ip()
        );

        return $response;
    }
}
