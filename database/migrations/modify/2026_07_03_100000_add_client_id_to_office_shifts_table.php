<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('office_shifts')) {
            return;
        }

        Schema::table('office_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('office_shifts', 'company_id')) {
                $table->dropForeign('office_shifts_company_id_foreign');
            }
        });

        Schema::table('office_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('office_shifts', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->change();
            }

            if (! Schema::hasColumn('office_shifts', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('company_id');
                $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            }

            if (Schema::hasColumn('office_shifts', 'company_id')) {
                $table->foreign('company_id', 'office_shifts_company_id_foreign')
                    ->references('id')
                    ->on('companies')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('office_shifts')) {
            return;
        }

        Schema::table('office_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('office_shifts', 'client_id')) {
                $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            }

            if (Schema::hasColumn('office_shifts', 'company_id')) {
                $table->dropForeign('office_shifts_company_id_foreign');
            }
        });

        Schema::table('office_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('office_shifts', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable(false)->change();
                $table->foreign('company_id', 'office_shifts_company_id_foreign')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('cascade');
            }
        });
    }
};
