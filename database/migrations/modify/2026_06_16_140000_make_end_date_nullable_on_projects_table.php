<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects') || ! Schema::hasColumn('projects', 'end_date')) {
            return;
        }

        DB::statement('ALTER TABLE `projects` MODIFY `end_date` DATE NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects') || ! Schema::hasColumn('projects', 'end_date')) {
            return;
        }

        DB::statement('ALTER TABLE `projects` MODIFY `end_date` DATE NOT NULL');
    }
};
