<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employee_project', 'is_lead')) {
            Schema::table('employee_project', function (Blueprint $table) {
                $table->boolean('is_lead')->default(false)->after('project_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employee_project', 'is_lead')) {
            Schema::table('employee_project', function (Blueprint $table) {
                $table->dropColumn('is_lead');
            });
        }
    }
};
