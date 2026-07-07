<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_categories')) {
            return;
        }

        if (Schema::hasColumn('project_categories', 'client_id')) {
            Schema::table('project_categories', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
            });

            try {
                Schema::table('project_categories', function (Blueprint $table) {
                    $table->dropUnique('project_categories_client_name_unique');
                });
            } catch (\Throwable $e) {
                //
            }

            Schema::table('project_categories', function (Blueprint $table) {
                $table->dropColumn('client_id');
            });
        }

        if (Schema::hasColumn('project_categories', 'company_id')) {
            Schema::table('project_categories', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }

        try {
            Schema::table('project_categories', function (Blueprint $table) {
                $table->unique('category_name', 'project_categories_name_unique');
            });
        } catch (\Throwable $e) {
            //
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_categories')) {
            return;
        }

        try {
            Schema::table('project_categories', function (Blueprint $table) {
                $table->dropUnique('project_categories_name_unique');
            });
        } catch (\Throwable $e) {
            //
        }

        Schema::table('project_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('project_categories', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            }

            if (! Schema::hasColumn('project_categories', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('company_id');
                $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
                $table->unique(['client_id', 'category_name'], 'project_categories_client_name_unique');
            }
        });
    }
};
