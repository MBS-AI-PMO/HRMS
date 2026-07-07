<?php

namespace App\Console\Commands;

use App\Models\location;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseLocationHeadAccess extends Command
{
    protected $signature = 'hrms:diagnose-location-head {username : Login username e.g. shahzad99}';

    protected $description = 'Diagnose why a user cannot see My Centers / Locations';

    public function handle(): int
    {
        $username = strtolower(trim((string) $this->argument('username')));
        $user = User::query()->whereRaw('LOWER(TRIM(username)) = ?', [$username])->first();

        if (! $user) {
            $this->error("User not found: {$username}");

            return self::FAILURE;
        }

        $identityIds = location::resolveIdentityIds((int) $user->id);
        $locationIds = location::locationIdsHeadedByUser((int) $user->id);
        $myPageIds = location::locationIdsForMyLocationsPage((int) $user->id);

        $this->info("User: {$user->username} (id={$user->id}, email={$user->email})");
        $this->line('Identity ids checked: '.implode(', ', $identityIds));
        $this->line('location_heads rows (by identity id):');

        foreach ($identityIds as $id) {
            $rows = DB::table('location_heads')->where('employee_id', $id)->get(['location_id', 'employee_id']);
            foreach ($rows as $row) {
                $this->line("  - location_id={$row->location_id}, employee_id={$row->employee_id}");
            }
        }

        if ($user->email) {
            $email = strtolower(trim($user->email));
            $emailRows = DB::table('location_heads')
                ->join('employees', 'employees.id', '=', 'location_heads.employee_id')
                ->whereRaw('LOWER(TRIM(employees.email)) = ?', [$email])
                ->select('location_heads.location_id', 'location_heads.employee_id', 'employees.email')
                ->get();

            $this->line('location_heads matched by employee email:');
            foreach ($emailRows as $row) {
                $this->line("  - location_id={$row->location_id}, employee_id={$row->employee_id}, email={$row->email}");
            }
        }

        $legacy = DB::table('locations')->whereIn('location_head', $identityIds)->pluck('location_name', 'id');
        $this->line('Legacy locations.location_head matches: '.$legacy->count());

        $this->line('Headed location ids: '.(implode(', ', $locationIds) ?: 'none'));
        $this->line('My Locations page ids: '.(implode(', ', $myPageIds) ?: 'none'));
        $this->line('Can access My Locations page: '.(location::userCanAccessMyLocationsPage((int) $user->id) ? 'YES' : 'NO'));

        return self::SUCCESS;
    }
}
