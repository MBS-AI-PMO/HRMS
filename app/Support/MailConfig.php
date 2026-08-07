<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * SMTP runtime config + auth fix for Laravel/Symfony Mailer.
 *
 * - ssl/465 with empty or "null" MAIL_SCHEME skips AUTH → 530 Authentication required
 * - Production (Coolify/Docker) often cannot rely on .env writes / config:cache, so
 *   portal mail settings are also persisted in cache and applied every request.
 */
class MailConfig
{
    public const CACHE_KEY = 'app.mail_settings';

    /**
     * Apply cached portal mail settings (if any), then ensure SMTP AUTH/scheme.
     */
    public static function boot(): void
    {
        self::applyCached();
        self::ensureSmtpAuth();
    }

    /**
     * @param  array{
     *     host?: string,
     *     port?: mixed,
     *     encryption?: string,
     *     scheme?: string,
     *     username?: string,
     *     password?: string|null,
     *     from_address?: string,
     *     from_name?: string
     * }  $settings
     * @param  bool  $persistPassword  When false, keep previously cached password if request left it blank.
     */
    public static function persist(array $settings, bool $persistPassword = true): void
    {
        $existing = self::cachedSettings();
        $password = $settings['password'] ?? null;

        if (! $persistPassword || $password === null || $password === '') {
            $password = $existing['password'] ?? (string) config('mail.mailers.smtp.password', '');
        }

        $normalized = self::normalize([
            'host' => $settings['host'] ?? ($existing['host'] ?? config('mail.mailers.smtp.host')),
            'port' => $settings['port'] ?? ($existing['port'] ?? config('mail.mailers.smtp.port')),
            'encryption' => $settings['encryption'] ?? ($existing['encryption'] ?? config('mail.mailers.smtp.encryption')),
            'username' => $settings['username'] ?? ($existing['username'] ?? config('mail.mailers.smtp.username')),
            'password' => $password,
            'from_address' => $settings['from_address'] ?? ($existing['from_address'] ?? config('mail.from.address')),
            'from_name' => $settings['from_name'] ?? ($existing['from_name'] ?? config('mail.from.name')),
        ]);

        Cache::forever(self::CACHE_KEY, [
            'host' => $normalized['host'],
            'port' => $normalized['port'],
            'encryption' => $normalized['encryption'],
            'scheme' => $normalized['scheme'],
            'username' => $normalized['username'],
            'password' => Crypt::encryptString((string) $normalized['password']),
            'from_address' => $normalized['from_address'],
            'from_name' => $normalized['from_name'],
        ]);

        self::apply($normalized);
    }

    public static function applyCached(): void
    {
        $settings = self::cachedSettings();

        if ($settings === null) {
            return;
        }

        self::apply($settings);
    }

    /**
     * @return array{
     *     host: string,
     *     port: mixed,
     *     encryption: string,
     *     scheme: string,
     *     username: string,
     *     password: string,
     *     from_address: string,
     *     from_name: string
     * }|null
     */
    public static function cachedSettings(): ?array
    {
        try {
            $cached = Cache::get(self::CACHE_KEY);
        } catch (Throwable $e) {
            return null;
        }

        if (! is_array($cached) || empty($cached['host'])) {
            return null;
        }

        $password = '';

        try {
            if (! empty($cached['password'])) {
                $password = Crypt::decryptString((string) $cached['password']);
            }
        } catch (Throwable $e) {
            $password = (string) ($cached['password'] ?? '');
        }

        return self::normalize([
            'host' => $cached['host'] ?? '',
            'port' => $cached['port'] ?? 587,
            'encryption' => $cached['encryption'] ?? 'tls',
            'username' => $cached['username'] ?? '',
            'password' => $password,
            'from_address' => $cached['from_address'] ?? '',
            'from_name' => $cached['from_name'] ?? '',
        ]);
    }

    /**
     * Values for the mail settings form (never expose password).
     *
     * @return array{host: string, port: mixed, encryption: string, username: string, from_address: string, from_name: string, has_password: bool}
     */
    public static function formValues(): array
    {
        $cached = self::cachedSettings();

        return [
            'host' => (string) ($cached['host'] ?? config('mail.mailers.smtp.host', '')),
            'port' => $cached['port'] ?? config('mail.mailers.smtp.port', 587),
            'encryption' => (string) ($cached['encryption'] ?? config('mail.mailers.smtp.encryption', 'tls')),
            'username' => (string) ($cached['username'] ?? config('mail.mailers.smtp.username', '')),
            'from_address' => (string) ($cached['from_address'] ?? config('mail.from.address', '')),
            'from_name' => (string) ($cached['from_name'] ?? config('mail.from.name', '')),
            'has_password' => (($cached['password'] ?? config('mail.mailers.smtp.password')) !== null)
                && (($cached['password'] ?? config('mail.mailers.smtp.password')) !== ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{
     *     host: string,
     *     port: mixed,
     *     encryption: string,
     *     scheme: string,
     *     username: string,
     *     password: string,
     *     from_address: string,
     *     from_name: string
     * }
     */
    public static function normalize(array $settings): array
    {
        $encryption = strtolower(trim((string) ($settings['encryption'] ?? 'tls')));
        $port = $settings['port'] ?? 587;
        $scheme = ($encryption === 'ssl' || (int) $port === 465) ? 'smtps' : 'smtp';
        $from = strtolower(trim((string) ($settings['from_address'] ?? '')));
        $username = strtolower(trim((string) ($settings['username'] ?? '')));

        if ($username === '' && $from !== '') {
            $username = $from;
        }

        return [
            'host' => trim((string) ($settings['host'] ?? '')),
            'port' => $port,
            'encryption' => $encryption,
            'scheme' => $scheme,
            'username' => $username,
            'password' => (string) ($settings['password'] ?? ''),
            'from_address' => $from,
            'from_name' => trim((string) ($settings['from_name'] ?? '')),
        ];
    }

    /**
     * @param  array{
     *     host: string,
     *     port: mixed,
     *     encryption: string,
     *     scheme: string,
     *     username: string,
     *     password: string,
     *     from_address: string,
     *     from_name: string
     * }  $settings
     */
    public static function apply(array $settings): void
    {
        $settings = self::normalize($settings);

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $settings['scheme'],
            'mail.mailers.smtp.host' => $settings['host'],
            'mail.mailers.smtp.port' => $settings['port'],
            'mail.mailers.smtp.encryption' => $settings['encryption'],
            'mail.mailers.smtp.username' => $settings['username'],
            'mail.mailers.smtp.password' => $settings['password'],
            'mail.host' => $settings['host'],
            'mail.port' => $settings['port'],
            'mail.encryption' => $settings['encryption'],
            'mail.username' => $settings['username'],
            'mail.password' => $settings['password'],
            'mail.from.address' => $settings['from_address'],
            'mail.from.name' => $settings['from_name'],
        ]);

        self::forgetMailer();
    }

    public static function ensureSmtpAuth(): void
    {
        $encryption = strtolower(trim((string) config('mail.mailers.smtp.encryption', config('mail.encryption', ''))));
        $port = (int) config('mail.mailers.smtp.port', config('mail.port', 587));
        $scheme = strtolower(trim((string) config('mail.mailers.smtp.scheme', '')));

        if ($scheme === '' || $scheme === 'null') {
            $scheme = ($encryption === 'ssl' || $port === 465) ? 'smtps' : 'smtp';
        }

        // ssl on 465 must use smtps or Symfony can skip AUTH.
        if ($encryption === 'ssl' || $port === 465) {
            $scheme = 'smtps';
        }

        $username = trim((string) config('mail.mailers.smtp.username', config('mail.username', '')));
        $password = (string) config('mail.mailers.smtp.password', config('mail.password', ''));
        $from = trim((string) config('mail.from.address', ''));

        if ($username === '' && $from !== '') {
            $username = $from;
        }

        config([
            'mail.default' => config('mail.default') ?: 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.username' => $username,
            'mail.mailers.smtp.password' => $password,
            'mail.username' => $username,
            'mail.password' => $password,
        ]);

        self::forgetMailer();
    }

    protected static function forgetMailer(): void
    {
        try {
            app()->forgetInstance('mail.manager');
            app()->forgetInstance('mailer');
        } catch (Throwable $e) {
            // ignore if container not ready
        }
    }
}
