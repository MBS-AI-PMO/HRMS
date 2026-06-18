<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DiagnoseMailDelivery extends Command
{
    protected $signature = 'mail:diagnose {domain? : Mail domain to check (defaults to FROM address domain)}';

    protected $description = 'Check SMTP config and DNS (SPF/DMARC) for outbound email deliverability';

    public function handle(): int
    {
        $from = strtolower(trim((string) config('mail.from.address')));
        $domain = strtolower(trim((string) ($this->argument('domain') ?: substr(strrchr($from, '@') ?: '', 1))));

        if ($domain === '') {
            $this->error('Could not detect domain. Set MAIL_FROM_ADDRESS or pass domain argument.');

            return self::FAILURE;
        }

        $this->info('HRMS mail delivery diagnosis');
        $this->newLine();

        $this->table(['Setting', 'Value'], [
            ['MAIL_MAILER', config('mail.default')],
            ['MAIL_HOST', config('mail.mailers.smtp.host')],
            ['MAIL_PORT', config('mail.mailers.smtp.port')],
            ['MAIL_ENCRYPTION', config('mail.mailers.smtp.encryption')],
            ['MAIL_USERNAME', config('mail.mailers.smtp.username') ?: '—'],
            ['MAIL_FROM', $from ?: '—'],
            ['Domain checked', $domain],
        ]);

        $this->newLine();
        $this->info('DNS records (public)');

        $this->checkTxt($domain);
        $this->checkTxt('_dmarc.'.$domain);
        $this->checkDkim($domain);

        $this->newLine();
        $this->warn('If laravel.log shows [MAIL AFTER] with message_id but Gmail inbox is empty:');
        $this->line('  1. App + SMTP are OK — your server accepted the message.');
        $this->line('  2. Gmail/Yahoo delivery depends on SPF, DKIM, DMARC on '.$domain);
        $this->line('  3. In cPanel: Email Deliverability → fix warnings for '.$domain);
        $this->line('  4. Send test from cPanel Webmail to the same Gmail — if still missing, it is hosting/DNS.');
        $this->line('  5. Check Spam + Promotions in Gmail; search: from:'.$from);
        $this->line('  6. Optional: use Mailgun / SendGrid / Amazon SES for reliable Gmail delivery.');

        return self::SUCCESS;
    }

    protected function checkTxt(string $name): void
    {
        $records = @dns_get_record($name, DNS_TXT) ?: [];

        if ($records === []) {
            $this->line("  <fg=red>MISSING</> TXT for {$name}");

            return;
        }

        foreach ($records as $record) {
            $txt = $record['txt'] ?? '';
            $this->line("  <fg=green>TXT</> {$name}: {$txt}");
        }
    }

    protected function checkDkim(string $domain): void
    {
        $selectors = ['default', 'selector1', 'selector2', 'google', 'k1', 'mail', 'solochoicez', 'solochoicezz'];

        $found = false;

        foreach ($selectors as $selector) {
            $host = "{$selector}._domainkey.{$domain}";
            $records = @dns_get_record($host, DNS_TXT) ?: [];

            foreach ($records as $record) {
                $txt = $record['txt'] ?? '';
                if ($txt !== '') {
                    $found = true;
                    $preview = strlen($txt) > 80 ? substr($txt, 0, 80).'...' : $txt;
                    $this->line("  <fg=green>DKIM</> {$host}: {$preview}");
                }
            }
        }

        if (! $found) {
            $this->line("  <fg=yellow>DKIM</> No common DKIM TXT found for {$domain} (enable DKIM in cPanel Email Deliverability)");
        }
    }
}
