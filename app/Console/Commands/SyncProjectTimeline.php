<?php

namespace App\Console\Commands;

use App\Services\ProjectTimelineService;
use Illuminate\Console\Command;

class SyncProjectTimeline extends Command
{
    protected $signature = 'projects:sync-timeline';

    protected $description = 'Recalculate project progress and status from start/end dates';

    public function handle(ProjectTimelineService $timelineService): int
    {
        $updated = $timelineService->syncAll();

        $this->info("Updated {$updated} project(s).");

        return self::SUCCESS;
    }
}
