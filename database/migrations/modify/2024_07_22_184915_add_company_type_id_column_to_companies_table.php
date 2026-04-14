<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('company_type');
            $table->foreignId('company_type_id')->after('company_name')->default(1)
                  ->foreign('company_type_id', 'companies_company_type_id_foreign')->references('id')->on('company_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign('companies_company_type_id_foreign');
            $table->dropColumn('company_type_id');
            $table->string('company_type', 191)->after('company_name');
        });
    }
};
