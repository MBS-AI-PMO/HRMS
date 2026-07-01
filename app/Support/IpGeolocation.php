<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpGeolocation
{
    public static function isResolvablePublicIp(?string $ip): bool
    {
        if ($ip === null || trim($ip) === '') {
            return false;
        }

        $ip = trim($ip);

        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        return true;
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public static function coordinatesForIp(?string $ip): ?array
    {
        if (! static::isResolvablePublicIp($ip)) {
            return null;
        }

        try {
            $response = Http::timeout(5)
                ->get('http://ip-api.com/json/'.urlencode($ip), [
                    'fields' => 'status,lat,lon',
                ]);

            if (! $response->ok()) {
                return null;
            }

            $payload = $response->json();

            if (($payload['status'] ?? '') !== 'success') {
                return null;
            }

            if (! is_numeric($payload['lat'] ?? null) || ! is_numeric($payload['lon'] ?? null)) {
                return null;
            }

            return [
                'latitude' => round((float) $payload['lat'], 7),
                'longitude' => round((float) $payload['lon'], 7),
            ];
        } catch (\Throwable $exception) {
            Log::warning('IP geolocation lookup failed', [
                'ip' => $ip,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
