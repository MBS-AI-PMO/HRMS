<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_deductions', function (Blueprint $table) {
            $table->dropColumn('deduction_type');
            $table->foreignId('deduction_type_id')->after('deduction_amount')->default(1)
                  ->foreign('deduction_type_id', 'salary_deductions_deduction_type_id_foreign')->references('id')->on('deduction_types');
        });
    }

    public function down(): void
    {
        Schema::table('salary_deductions', function (Blueprint $table) {
            $table->dropForeign('salary_deductions_deduction_type_id_foreign');
            $table->dropColumn('deduction_type_id');
            $table->string('deduction_type', 191)->after('deduction_amount');
        });
    }
};
