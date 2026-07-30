<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DiagnoseFcmPush extends Command
{
    protected $signature = 'fcm:diagnose {user? : Optional user id or username to inspect token}';

    protected $description = 'Diagnose Firebase push setup (credentials, queue, tokens, test send)';

    public function handle(FirebaseNotificationService $fcm): int
    {
        $this->info('=== FCM Diagnose ===');

        $queue = (string) config('queue.default');
        $this->line('QUEUE_CONNECTION: '.$queue);
        if ($queue !== 'sync') {
            $this->warn('Many notifications use ShouldQueue. If no queue worker is running, pushes will wait forever.');
            $this->line('Fix: set QUEUE_CONNECTION=sync OR run: php artisan queue:work');
            if ($queue === 'database' && Schema::hasTable('jobs')) {
                $pending = DB::table('jobs')->count();
                $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
                $this->line("Pending jobs: {$pending} | Failed jobs: {$failed}");
            }
        } else {
            $this->info('Queue is sync — notifications run immediately (good for push).');
        }

        $configured = (string) config('services.firebase.credentials');
        $resolved = is_file($configured) ? $configured : storage_path($configured);
        $this->line('FIREBASE_CREDENTIALS config: '.$configured);
        $this->line('Resolved path: '.$resolved);
        $this->line('File exists: '.(is_file($resolved) ? 'YES' : 'NO'));
        $this->line('FIREBASE_PROJECT_ID: '.(string) (config('services.firebase.project_id') ?: '(from JSON)'));

        if (! is_file($resolved)) {
            $this->error('Credentials JSON missing. Upload it then re-run.');

            return self::FAILURE;
        }

        $json = json_decode((string) file_get_contents($resolved), true);
        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            $this->error('Credentials JSON invalid (need client_email + private_key).');

            return self::FAILURE;
        }
        $this->info('Credentials JSON looks valid ('.$json['client_email'].')');

        if (! Schema::hasColumn('users', 'fcm_token')) {
            $this->error('users.fcm_token column missing — run php artisan migrate');

            return self::FAILURE;
        }

        $withToken = User::query()->whereNotNull('fcm_token')->where('fcm_token', '!=', '')->count();
        $this->line("Users with fcm_token: {$withToken}");

        $identifier = $this->argument('user');
        if (! $identifier) {
            $this->line('Tip: php artisan fcm:diagnose username   then   php artisan fcm:test username');

            return self::SUCCESS;
        }

        $user = is_numeric($identifier)
            ? User::find((int) $identifier)
            : User::where('username', strtolower((string) $identifier))->first();

        if (! $user) {
            $this->error('User not found: '.$identifier);

            return self::FAILURE;
        }

        $this->line("User #{$user->id} ({$user->username})");
        if (empty($user->fcm_token)) {
            $this->error('No fcm_token — open mobile app, login again so POST /api/save-fcm-token runs.');

            return self::FAILURE;
        }

        $this->line('Token prefix: '.substr((string) $user->fcm_token, 0, 28).'...');

        try {
            $ok = $fcm->sendToUser(
                (int) $user->id,
                'FCM Diagnose',
                'If you see this, push works.',
                ['type' => 'diagnose', 'sent_at' => now()->toDateTimeString()]
            );
            if ($ok) {
                $this->info('Test push ACCEPTED by FCM — check the phone.');

                return self::SUCCESS;
            }
            $this->error('Test push REJECTED — check storage/logs/laravel.log for FCM send failed / FCM send error');

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
