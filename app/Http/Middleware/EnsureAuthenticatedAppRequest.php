<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires a logged-in session and blocks obvious cross-origin mutation requests.
 * CSRF still applies on web routes; this adds defense-in-depth for scripted attacks.
 */
class EnsureAuthenticatedAppRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            if ($request->expectsJson() || $request->ajax()) {
                abort(401, __('Unauthenticated.'));
            }

            return redirect()->guest(route('login'));
        }

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

            if ($appHost) {
                foreach (['origin', 'referer'] as $headerName) {
                    $header = (string) $request->headers->get($headerName, '');

                    if ($header === '') {
                        continue;
                    }

                    $requestHost = parse_url($header, PHP_URL_HOST);

                    if ($requestHost && strcasecmp($requestHost, $appHost) !== 0) {
                        abort(403, __('Invalid request origin.'));
                    }
                }
            }
        }

        return $next($request);
    }
}
