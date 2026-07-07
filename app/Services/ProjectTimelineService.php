<?php

namespace App\Services;

use App\Models\Project;
use Carbon\Carbon;

class ProjectTimelineService
{
    public function apply(Project $project): void
    {
        $start = $this->parseStoredDate($project->getAttributes()['start_date'] ?? null);
        $end = $this->parseStoredDate($project->getAttributes()['end_date'] ?? null);
        $progress = $this->calculateProgress($start, $end);

        $project->project_progress = (string) $progress;

        $status = $this->normalizeStatus($project->getAttributes()['project_status'] ?? null);

        if ($status === 'deferred') {
            return;
        }

        if ($progress >= 100 && $end && Carbon::today()->gte($end)) {
            $project->project_status = 'completed';

            return;
        }

        if ($status === 'completed' && $progress < 100) {
            $project->project_status = 'in_progress';

            return;
        }

        if (in_array($status, ['', 'not_started'], true)) {
            $project->project_status = 'in_progress';
        }
    }

    public function calculateProgress(?Carbon $start, ?Carbon $end, ?Carbon $today = null): int
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();

        if (! $start) {
            return 0;
        }

        $start = $start->copy()->startOfDay();

        if ($today->lt($start)) {
            return 0;
        }

        if (! $end) {
            return 0;
        }

        $end = $end->copy()->startOfDay();

        if ($end->lte($start)) {
            return $today->gte($end) ? 100 : 0;
        }

        if ($today->gte($end)) {
            return 100;
        }

        $totalDays = max(1, $start->diffInDays($end));
        $elapsedDays = $start->diffInDays($today);

        return (int) min(100, max(0, round(($elapsedDays / $totalDays) * 100)));
    }

    public function syncAll(): int
    {
        $updated = 0;

        Project::query()
            ->orderBy('id')
            ->chunkById(100, function ($projects) use (&$updated) {
                foreach ($projects as $project) {
                    $beforeProgress = (string) ($project->project_progress ?? '');
                    $beforeStatus = $this->normalizeStatus($project->getAttributes()['project_status'] ?? null);

                    $this->apply($project);

                    $afterProgress = (string) ($project->project_progress ?? '');
                    $afterStatus = $this->normalizeStatus($project->getAttributes()['project_status'] ?? null);

                    if ($beforeProgress !== $afterProgress || $beforeStatus !== $afterStatus) {
                        $project->saveQuietly();
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    protected function parseStoredDate(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->startOfDay();
    }

    protected function normalizeStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        if ($status === 'not started') {
            return 'not_started';
        }

        return $status;
    }
}
