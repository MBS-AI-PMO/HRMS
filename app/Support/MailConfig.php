<?php

namespace App\Support;

/**
 * Ensures Symfony Mailer authenticates on SMTP (fixes 530 Authentication required
 * when MAIL_ENCRYPTION=ssl / port 465 but MAIL_SCHEME is null/empty).
 */
class MailConfig
{
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
        ]);

        // Force MailManager to rebuild transport with corrected DSN on next send.
        try {
            app()->forgetInstance('mail.manager');
            app()->forgetInstance('mailer');
        } catch (\Throwable $e) {
            // ignore if container not ready
        }
    }
}
