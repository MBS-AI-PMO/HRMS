<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\leave;
use Illuminate\Console\Command;

class SyncWfhAttendanceType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:sync-wfh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync attendance type for approved WFH leaves';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = now()->toDateString();

        $activeWfhEmployeeIds = leave::query()
            ->join('leave_types', 'leave_types.id', '=', 'leaves.leave_type_id')
            ->where('leaves.status', 'approved')
            ->where('leaves.hr_approval_status', 'approved')
            ->where('leaves.manager_approval_status', 'approved')
            ->whereDate('leaves.start_date', '<=', $today)
            ->whereDate('leaves.end_date', '>=', $today)
            ->where(function ($query) {
                $query->where('leave_types.leave_type', 'like', '%wfh%')
                    ->orWhere('leave_types.leave_type', 'like', '%work from home%');
            })
            ->distinct()
            ->pluck('leaves.employee_id');

        $toGeneral = Employee::query()
            ->whereIn('id', $activeWfhEmployeeIds)
            ->where('attendance_type', '!=', 'general')
            ->update(['attendance_type' => 'general']);

        $toLocationBased = Employee::query()
            ->whereNotIn('id', $activeWfhEmployeeIds)
            ->where('attendance_type', 'general')
            ->update(['attendance_type' => 'location_based']);

        $this->info("WFH sync done. general={$toGeneral}, location_based={$toLocationBased}");

        return self::SUCCESS;
    }
}
