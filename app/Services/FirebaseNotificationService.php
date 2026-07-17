<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;


/**
 * Sends push notifications through the Firebase Cloud Messaging HTTP v1 API.
 *
 * No external SDK is required: the OAuth2 access token is minted locally by
 * signing a JWT with the service-account private key (RS256 via openssl).
 */
class FirebaseNotificationService
{
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const CACHE_KEY = 'fcm_access_token';

    /**
     * Send a push to a single device token.
     *
     * @param  array<string, mixed>  $data  Extra data payload (values are cast to string by FCM).
     * @return bool True when FCM accepted the message.
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $token = trim($token);
        if ($token === '') {
            return false;
        }

        try {
            $message = $this->buildMessage($token, $title, $body, $data);

            [$status, $rawBody] = $this->httpPost(
                $this->messagesEndpoint(),
                (string) json_encode(['message' => $message]),
                [
                    'Authorization: Bearer '.$this->accessToken(),
                    'Content-Type: application/json',
                    'Accept: application/json',
                ]
            );

            if ($status >= 200 && $status < 300) {
                return true;
            }

            $json = json_decode($rawBody, true);

            // Stale/unregistered token — drop it so we stop targeting a dead device.
            if (in_array($status, [400, 404], true)
                && $this->isUnregistered(is_array($json) ? $json : null)) {
                User::where('fcm_token', $token)->update(['fcm_token' => null]);
            }

            Log::warning('FCM send failed', [
                'status' => $status,
                'body' => $rawBody,
            ]);

            return false;
        } catch (Throwable $e) {
            Log::error('FCM send error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Send a push to a user by id (reads fcm_token from the users table).
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): bool
    {
        $token = (string) (User::whereKey($userId)->value('fcm_token') ?? '');
        if ($token === '') {
            return false;
        }

        return $this->sendToToken($token, $title, $body, $data);
    }

    /**
     * Send a push to many users by id.
     *
     * @param  array<int>  $userIds
     * @return int Number of devices the message was accepted for.
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): int
    {
        $tokens = User::whereIn('id', array_filter($userIds))
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->filter()
            ->unique()
            ->values();

        $sent = 0;
        foreach ($tokens as $token) {
            if ($this->sendToToken((string) $token, $title, $body, $data)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Build the FCM v1 "message" object.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildMessage(string $token, string $title, string $body, array $data): array
    {
        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        return [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $stringData,
            'android' => [
                'priority' => 'high',
                'notification' => ['sound' => 'default'],
            ],
            'apns' => [
                'payload' => [
                    'aps' => ['sound' => 'default'],
                ],
            ],
        ];
    }

    private function isUnregistered(?array $json): bool
    {
        $status = $json['error']['status'] ?? '';
        $message = $json['error']['message'] ?? '';

        return $status === 'NOT_FOUND'
            || $status === 'UNREGISTERED'
            || str_contains((string) $message, 'not a valid FCM registration token')
            || str_contains((string) $message, 'Requested entity was not found');
    }

    private function messagesEndpoint(): string
    {
        $projectId = config('services.firebase.project_id')
            ?: ($this->credentials()['project_id'] ?? null);

        if (! $projectId) {
            throw new RuntimeException('Firebase project_id is not configured.');
        }

        return "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    }

    /**
     * Get a cached OAuth2 access token, minting a fresh one when needed.
     */
    private function accessToken(): string
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $credentials = $this->credentials();
        $assertion = $this->buildSignedJwt($credentials);

        [$status, $rawBody] = $this->httpPost(
            self::TOKEN_URI,
            http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]),
            [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ]
        );

        $json = json_decode($rawBody, true);

        if ($status < 200 || $status >= 300 || empty($json['access_token'])) {
            throw new RuntimeException('Unable to obtain Firebase access token: '.$rawBody);
        }

        $accessToken = (string) $json['access_token'];
        $expiresIn = (int) ($json['expires_in'] ?? 3600);

        // Refresh a minute early to avoid edge-of-expiry failures.
        Cache::put(self::CACHE_KEY, $accessToken, max(60, $expiresIn - 60));

        return $accessToken;
    }

    /**
     * Create and sign the JWT used to request an access token.
     *
     * @param  array<string, mixed>  $credentials
     */
    private function buildSignedJwt(array $credentials): string
    {
        $clientEmail = $credentials['client_email'] ?? null;
        $privateKey = $credentials['private_key'] ?? null;

        if (! $clientEmail || ! $privateKey) {
            throw new RuntimeException('Service account JSON is missing client_email or private_key.');
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $clientEmail,
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URI,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($claims)),
        ];
        $signingInput = implode('.', $segments);

        $signature = '';
        if (! openssl_sign($signingInput, $signature, $privateKey, 'sha256WithRSAEncryption')) {
            throw new RuntimeException('Failed to sign Firebase JWT with the service-account key.');
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * Minimal cURL POST so this service does not depend on Guzzle / the HTTP client.
     *
     * @param  array<int, string>  $headers
     * @return array{0:int, 1:string}  [statusCode, responseBody]
     */
    private function httpPost(string $url, string $body, array $headers): array
    {
        if (! function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is required to send FCM notifications.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new RuntimeException('HTTP request to '.$url.' failed: '.$error);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, (string) $response];
    }

    /**
     * Load and decode the service-account JSON key (cached in memory per request).
     *
     * @return array<string, mixed>
     */
    private function credentials(): array
    {
        static $decoded = null;
        if (is_array($decoded)) {
            return $decoded;
        }

        $path = (string) config('services.firebase.credentials');
        if ($path === '') {
            throw new RuntimeException('FIREBASE_CREDENTIALS is not set.');
        }

        // Allow a path relative to storage_path() for convenience.
        if (! is_file($path)) {
            $path = storage_path($path);
        }

        if (! is_file($path)) {
            throw new RuntimeException('Firebase service-account JSON not found at: '.$path);
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json)) {
            throw new RuntimeException('Firebase service-account JSON is invalid.');
        }

        return $decoded = $json;
    }
}
