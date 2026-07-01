<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'location-head-access',
            'view-my-locations',
            'scoped-view-employees',
            'scoped-manage-leave',
            'report-clock-in-locations',
        ];

        foreach ($permissions as $name) {
            if (! DB::table('permissions')->where('name', $name)->exists()) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', [
            'location-head-access',
            'view-my-locations',
            'scoped-view-employees',
            'scoped-manage-leave',
            'report-clock-in-locations',
        ])->delete();
    }
};
