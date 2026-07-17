<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Console\Command;
use Throwable;

class TestFcmPush extends Command
{
    protected $signature = 'fcm:test {user : User id or username}
        {--title=Test Notification : Notification title}
        {--body=This is a test push from HRMS. : Notification body}';

    protected $description = 'Send a test FCM push notification to a user to verify the setup';

    public function handle(FirebaseNotificationService $fcm): int
    {
        $identifier = trim((string) $this->argument('user'));

        $user = is_numeric($identifier)
            ? User::find((int) $identifier)
            : User::where('username', strtolower($identifier))->first();

        if (! $user) {
            $this->error('User not found: '.$identifier);

            return self::FAILURE;
        }

        if (empty($user->fcm_token)) {
            $this->error("User #{$user->id} ({$user->username}) has no fcm_token saved. Login from the app first.");

            return self::FAILURE;
        }

        $this->line('Sending to user #'.$user->id.' ('.$user->username.')');
        $this->line('Token: '.substr((string) $user->fcm_token, 0, 24).'...');

        try {
            $ok = $fcm->sendToUser(
                (int) $user->id,
                (string) $this->option('title'),
                (string) $this->option('body'),
                ['type' => 'test', 'sent_at' => now()->toDateTimeString()]
            );

            if ($ok) {
                $this->info('Push accepted by FCM. Check the device.');

                return self::SUCCESS;
            }

            $this->error('FCM did not accept the message. Check storage/logs/laravel.log for details.');

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error('FCM error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
