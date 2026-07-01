<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Support\IpGeolocation;
use Illuminate\Console\Command;

class BackfillAttendanceCoordinatesFromIp extends Command
{
    protected $signature = 'attendance:backfill-coordinates-from-ip
                            {--dry-run : Show what would be updated without saving}';

    protected $description = 'Fill missing clock-in latitude/longitude from public IP or assigned office location';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = Attendance::query()
            ->with(['employee.location:id,latitude,longitude'])
            ->whereNotNull('clock_in')
            ->where(function ($builder) {
                $builder->whereNull('clock_in_latitude')
                    ->orWhereNull('clock_in_longitude');
            })
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No attendance rows need backfill.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Processing {$total} attendance row(s)...");

        $updated = 0;
        $skipped = 0;
        $fromIp = 0;
        $fromOffice = 0;

        $query->chunkById(50, function ($rows) use ($dryRun, &$updated, &$skipped, &$fromIp, &$fromOffice) {
            foreach ($rows as $attendance) {
                $coordinates = IpGeolocation::coordinatesForIp($attendance->clock_in_ip);
                $source = 'ip';

                if ($coordinates === null) {
                    $office = optional(optional($attendance->employee)->location);

                    if ($office && $office->latitude !== null && $office->longitude !== null) {
                        $coordinates = [
                            'latitude' => round((float) $office->latitude, 7),
                            'longitude' => round((float) $office->longitude, 7),
                        ];
                        $source = 'office';
                    }
                }

                if ($coordinates === null) {
                    $skipped++;
                    $this->line("Skipped #{$attendance->id} (no IP lookup and no office location)");

                    continue;
                }

                $this->line(sprintf(
                    '%s #%d via %s -> lat %s, lng %s',
                    $dryRun ? 'Would update' : 'Updated',
                    $attendance->id,
                    $source,
                    $coordinates['latitude'],
                    $coordinates['longitude']
                ));

                if (! $dryRun) {
                    $attendance->update([
                        'clock_in_latitude' => $coordinates['latitude'],
                        'clock_in_longitude' => $coordinates['longitude'],
                    ]);
                }

                $updated++;

                if ($source === 'ip') {
                    $fromIp++;
                    usleep(250000);
                } else {
                    $fromOffice++;
                }
            }
        });

        $this->newLine();
        $this->info("Done. Updated: {$updated} (IP: {$fromIp}, office fallback: {$fromOffice}), skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
