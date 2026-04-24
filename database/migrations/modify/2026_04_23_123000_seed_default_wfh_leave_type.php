<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedDefaultWfhLeaveType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $exists = DB::table('leave_types')
            ->where(function ($query) {
                $query->where('leave_type', 'WFH')
                    ->orWhere('leave_type', 'Work From Home');
            })
            ->exists();

        if (! $exists) {
            DB::table('leave_types')->insert([
                'leave_type' => 'WFH',
                'allocated_day' => 365,
                'company_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('leave_types')
            ->where('leave_type', 'WFH')
            ->whereNull('company_id')
            ->delete();
    }
}
