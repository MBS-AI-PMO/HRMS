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
            foreach (['origin', 'referer'] as $headerName) {
                $header = (string) $request->headers->get($headerName, '');

                if ($header === '') {
                    continue;
                }

                $originHost = parse_url($header, PHP_URL_HOST);

                if ($originHost && ! $this->isTrustedOriginHost((string) $originHost, $request)) {
                    abort(403, __('Invalid request origin.'));
                }
            }
        }

        return $next($request);
    }

    protected function isTrustedOriginHost(string $originHost, Request $request): bool
    {
        $originHost = strtolower($originHost);

        $trustedHosts = array_filter([
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
            strtolower($request->getHost()),
        ]);

        foreach ($trustedHosts as $trustedHost) {
            if ($originHost === $trustedHost || $this->localHostsEquivalent($originHost, $trustedHost)) {
                return true;
            }
        }

        return false;
    }

    protected function localHostsEquivalent(string $a, string $b): bool
    {
        $localHosts = ['localhost', '127.0.0.1', '::1'];

        return in_array(strtolower($a), $localHosts, true)
            && in_array(strtolower($b), $localHosts, true);
    }
}
