<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teams')) {
            return;
        }

        Schema::table('teams', function (Blueprint $table) {
            if (! Schema::hasColumn('teams', 'department_head_id')) {
                $table->unsignedBigInteger('department_head_id')->nullable()->after('department_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teams')) {
            return;
        }

        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'department_head_id')) {
                $table->dropColumn('department_head_id');
            }
        });
    }
};
