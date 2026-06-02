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
        $statusCode = method_exists($response, 'getStatusCode') ? (int) $response->getStatusCode() : null;
        $responseBody = method_exists($response, 'getContent') ? (string) $response->getContent() : '';
        $responseJson = json_decode($responseBody, true);
        $isJson = json_last_error() === JSON_ERROR_NONE && is_array($responseJson);

        $isFailed = ($statusCode !== null && $statusCode >= 400);
        if ($isJson) {
            if (!empty($responseJson['errors']) || !empty($responseJson['error'])) {
                $isFailed = true;
            }
        }

        $action = 'request.' . strtolower($request->method()) . ($isFailed ? '.failed' : '.success');
        $description = ($isFailed ? 'Request failed: ' : 'Request successful: ') . $routeName;

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
            $action,
            $description,
            [
                'route' => $routeName,
                'method' => strtoupper($request->method()),
                'path' => $request->path(),
                'payload' => $payload,
                'response_status' => $statusCode,
                'failed' => $isFailed,
            ],
            $request->ip()
        );

        return $response;
    }
}
