<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_loans', function (Blueprint $table) {
            $table->dropColumn('loan_type');
            $table->foreignId('loan_type_id')->after('loan_amount')->default(1)
                  ->foreign('loan_type_id', 'salary_loans_loan_type_id_foreign')->references('id')->on('loan_types');
        });
    }

    public function down(): void
    {
        Schema::table('salary_loans', function (Blueprint $table) {
            $table->dropForeign('salary_loans_loan_type_id_foreign');
            $table->dropColumn('loan_type_id');
            $table->string('loan_type', 191)->after('loan_amount');
        });
    }
};
