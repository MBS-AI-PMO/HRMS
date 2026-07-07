<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects') || Schema::hasColumn('projects', 'project_category_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('project_category_id')->nullable()->after('client_id');
            $table->foreign('project_category_id')
                ->references('id')
                ->on('project_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('projects', 'project_category_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['project_category_id']);
            $table->dropColumn('project_category_id');
        });
    }
};
