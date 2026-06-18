<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Scopes\AuthCompanyScope;
use App\Services\NotificationRecipientResolver;
use Illuminate\Console\Command;

class SyncEmployeeUserEmails extends Command
{
    protected $signature = 'hrms:sync-user-emails {--dry-run : List accounts only, do not update}';

    protected $description = 'Copy employees.email into users.email where the login account has no email';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $synced = 0;
        $missing = 0;

        Employee::query()
            ->withoutGlobalScope(AuthCompanyScope::class)
            ->orderBy('id')
            ->chunkById(200, function ($employees) use ($dryRun, &$synced, &$missing) {
                foreach ($employees as $employee) {
                    $userId = (int) $employee->id;
                    $source = NotificationRecipientResolver::emailSourceForUser($userId);

                    if ($source === 'users') {
                        continue;
                    }

                    if ($source === 'employees') {
                        $email = strtolower(trim((string) $employee->email));

                        if ($dryRun) {
                            $this->line("Would sync user #{$userId}: {$email}");
                        } else {
                            NotificationRecipientResolver::syncEmailToUserAccount($userId, $email);
                            $this->line("Synced user #{$userId}: {$email}");
                        }

                        $synced++;

                        continue;
                    }

                    $missing++;
                    $this->warn("No valid email for employee #{$userId} ({$employee->full_name})");
                }
            });

        $this->newLine();
        $this->info(($dryRun ? 'Would sync' : 'Synced').": {$synced}");
        $this->info("Still missing email: {$missing}");

        return self::SUCCESS;
    }
}
