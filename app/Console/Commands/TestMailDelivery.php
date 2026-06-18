<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestMailDelivery extends Command
{
    protected $signature = 'mail:test {email : Recipient email address}';

    protected $description = 'Send a test email via the configured SMTP server to verify delivery';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error('Invalid email address: '.$email);

            return self::FAILURE;
        }

        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');
        $sentAt = now()->toDateTimeString();

        try {
            Mail::raw(
                "This is a test email from HRMS.\n\nSent at: {$sentAt}\nFrom: {$fromAddress}\nHost: ".config('mail.host'),
                function ($message) use ($email, $fromAddress, $fromName, $sentAt) {
                    $message->from($fromAddress, $fromName)
                        ->replyTo($fromAddress, $fromName)
                        ->to($email)
                        ->subject('HRMS SMTP test - '.$sentAt);
                }
            );

            Log::info('Mail test sent', [
                'email' => $email,
                'mail_from' => $fromAddress,
                'mail_host' => config('mail.host'),
            ]);

            $this->info("Test email handed off to SMTP for: {$email}");
            $this->line('From: '.$fromAddress);
            $this->line('Host: '.config('mail.host'));
            $this->line('If inbox is empty, check Spam/Promotions and your mail server delivery logs.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::error('Mail test failed', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);

            $this->error('SMTP error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
