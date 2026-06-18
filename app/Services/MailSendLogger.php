<?php

namespace App\Services;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class MailSendLogger
{
    protected ?array $beforePayload = null;

    protected ?array $afterPayload = null;

    /**
     * @param  callable(): void  $sendCallback
     * @return array{before: array<string, mixed>, after: array<string, mixed>|null}
     */
    public function wrap(string $context, array $meta, callable $sendCallback): array
    {
        $this->beforePayload = array_merge($meta, [
            'stage' => 'BEFORE_MAIL',
            'context' => $context,
            'mail_driver' => config('mail.default'),
            'mail_host' => config('mail.mailers.smtp.host'),
            'mail_port' => config('mail.mailers.smtp.port'),
            'mail_encryption' => config('mail.mailers.smtp.encryption'),
            'mail_from' => config('mail.from.address'),
            'mail_username' => config('mail.mailers.smtp.username'),
        ]);

        Log::info('[MAIL BEFORE] '.$context, $this->beforePayload);

        $sendingListener = function (MessageSending $event) {
            $symfony = $event->message;
            $to = array_map(
                static fn ($addr) => $addr->getAddress(),
                $symfony->getTo() ?? []
            );
            $from = array_map(
                static fn ($addr) => $addr->getAddress(),
                $symfony->getFrom() ?? []
            );

            Log::info('[MAIL BEFORE] Symfony message prepared', [
                'to' => $to,
                'from' => $from,
                'subject' => $symfony->getSubject(),
            ]);
        };

        $sentListener = function (MessageSent $event) {
            $messageId = null;

            try {
                $messageId = $event->sent->getSymfonySentMessage()?->getMessageId();
            } catch (\Throwable) {
                // ignore
            }

            $this->afterPayload = array_merge($this->beforePayload ?? [], [
                'stage' => 'AFTER_MAIL',
                'smtp_handoff' => true,
                'message_id' => $messageId,
            ]);

            Log::info('[MAIL AFTER] SMTP accepted message', $this->afterPayload);
        };

        Event::listen(MessageSending::class, $sendingListener);
        Event::listen(MessageSent::class, $sentListener);

        try {
            $sendCallback();
        } finally {
            Event::forget(MessageSending::class);
            Event::forget(MessageSent::class);
        }

        if ($this->afterPayload === null) {
            $this->afterPayload = array_merge($this->beforePayload ?? [], [
                'stage' => 'AFTER_MAIL',
                'smtp_handoff' => true,
                'message_id' => null,
                'note' => 'MessageSent event did not fire — check mail driver is not log/array.',
            ]);

            Log::warning('[MAIL AFTER] No MessageSent event', $this->afterPayload);
        }

        return [
            'before' => $this->beforePayload ?? [],
            'after' => $this->afterPayload,
        ];
    }

    public static function recipientDomainHint(string $email): ?string
    {
        $email = strtolower(trim($email));
        $domain = substr(strrchr($email, '@') ?: '', 1);
        $fromDomain = strtolower((string) substr(strrchr((string) config('mail.from.address'), '@') ?: '', 1));

        if ($domain === '' || $fromDomain === '') {
            return null;
        }

        $externalProviders = ['gmail.com', 'googlemail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com'];

        if (in_array($domain, $externalProviders, true) && $domain !== $fromDomain) {
            return __('Recipient uses :domain. If inbox is empty but log shows AFTER_MAIL, your mail server accepted the message but Gmail/Yahoo may block it. Ask hosting to enable SPF + DKIM for :from_domain, and check server mail delivery logs / spam folder.', [
                'domain' => $domain,
                'from_domain' => $fromDomain,
            ]);
        }

        return null;
    }
}
