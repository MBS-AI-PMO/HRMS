<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('team_department_heads')) {
            Schema::create('team_department_heads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('team_id');
                $table->unsignedBigInteger('employee_id');
                $table->timestamps();

                $table->unique(['team_id', 'employee_id']);
            });
        }

        if (Schema::hasTable('teams') && Schema::hasColumn('teams', 'department_head_id')) {
            $rows = DB::table('teams')
                ->whereNotNull('department_head_id')
                ->where('department_head_id', '>', 0)
                ->get(['id', 'department_head_id']);

            foreach ($rows as $row) {
                $exists = DB::table('team_department_heads')
                    ->where('team_id', $row->id)
                    ->where('employee_id', $row->department_head_id)
                    ->exists();

                if (! $exists) {
                    DB::table('team_department_heads')->insert([
                        'team_id' => $row->id,
                        'employee_id' => $row->department_head_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::table('teams', function (Blueprint $table) {
                $table->dropColumn('department_head_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teams') && ! Schema::hasColumn('teams', 'department_head_id')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->unsignedBigInteger('department_head_id')->nullable()->after('department_id');
            });

            $heads = DB::table('team_department_heads')
                ->select('team_id', DB::raw('MIN(employee_id) as employee_id'))
                ->groupBy('team_id')
                ->get();

            foreach ($heads as $head) {
                DB::table('teams')->where('id', $head->team_id)->update([
                    'department_head_id' => $head->employee_id,
                ]);
            }
        }

        Schema::dropIfExists('team_department_heads');
    }
};
