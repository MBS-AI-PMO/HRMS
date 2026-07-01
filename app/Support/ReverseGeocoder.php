<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReverseGeocoder
{
    public static function placeName(?float $lat, ?float $lng): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        $cacheKey = static::cacheKey($lat, $lng);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $placeName = static::lookupPlaceName($lat, $lng);

        if ($placeName !== null) {
            Cache::put($cacheKey, $placeName, now()->addDays(30));
        }

        return $placeName;
    }

    public static function cachedPlaceName(?float $lat, ?float $lng): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        return Cache::get(static::cacheKey($lat, $lng));
    }

    public static function rememberPlaceName(float $lat, float $lng, ?string $placeName): ?string
    {
        if ($placeName === null || trim($placeName) === '') {
            return null;
        }

        $placeName = trim($placeName);
        Cache::put(static::cacheKey($lat, $lng), $placeName, now()->addDays(30));

        return $placeName;
    }

    protected static function cacheKey(float $lat, float $lng): string
    {
        return 'reverse_geocode_'.round($lat, 5).'_'.round($lng, 5);
    }

    protected static function lookupPlaceName(float $lat, float $lng): ?string
    {
        $payload = static::fetchPayload($lat, $lng);

        return static::formatPayload($payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function fetchPayload(float $lat, float $lng): ?array
    {
        $query = [
            'lat' => $lat,
            'lon' => $lng,
            'format' => 'json',
            'zoom' => 18,
            'addressdetails' => 1,
        ];

        try {
            $response = Http::timeout(12)
                ->withHeaders(static::requestHeaders())
                ->get('https://nominatim.openstreetmap.org/reverse', $query);

            if ($response->ok()) {
                $payload = $response->json();

                return is_array($payload) ? $payload : null;
            }
        } catch (\Throwable $exception) {
            Log::warning('Reverse geocode HTTP lookup failed', [
                'lat' => $lat,
                'lng' => $lng,
                'message' => $exception->getMessage(),
            ]);
        }

        return static::fetchPayloadViaStream($lat, $lng, $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    protected static function fetchPayloadViaStream(float $lat, float $lng, array $query): ?array
    {
        $url = 'https://nominatim.openstreetmap.org/reverse?'.http_build_query($query);

        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", [
                        'User-Agent: '.static::userAgent(),
                        'Accept: application/json',
                        'Accept-Language: en',
                    ]),
                    'timeout' => 12,
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);

            $body = @file_get_contents($url, false, $context);

            if ($body === false || $body === '') {
                return null;
            }

            $payload = json_decode($body, true);

            return is_array($payload) ? $payload : null;
        } catch (\Throwable $exception) {
            Log::warning('Reverse geocode stream lookup failed', [
                'lat' => $lat,
                'lng' => $lng,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public static function formatPayload(?array $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        if (! empty($payload['display_name'])) {
            return (string) $payload['display_name'];
        }

        $address = $payload['address'] ?? null;

        if (! is_array($address)) {
            return ! empty($payload['name']) ? (string) $payload['name'] : null;
        }

        $parts = array_filter([
            $address['shop'] ?? null,
            $address['amenity'] ?? null,
            $address['building'] ?? null,
            $address['road'] ?? null,
            $address['suburb'] ?? null,
            $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
            $address['state'] ?? null,
            $address['country'] ?? null,
        ]);

        if ($parts === []) {
            return ! empty($payload['name']) ? (string) $payload['name'] : null;
        }

        return implode(', ', array_unique($parts));
    }

    /**
     * @return array<string, string>
     */
    protected static function requestHeaders(): array
    {
        return [
            'User-Agent' => static::userAgent(),
            'Accept' => 'application/json',
            'Accept-Language' => 'en',
        ];
    }

    protected static function userAgent(): string
    {
        return trim((string) config('app.name', 'HRMS')).' Clock-in Location Report';
    }
}
