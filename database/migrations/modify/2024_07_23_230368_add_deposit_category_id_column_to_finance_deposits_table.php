<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_deposits', function (Blueprint $table) {
            $table->dropColumn('category');
            $table->foreignId('deposit_category_id')->after('amount')->default(1)
                  ->foreign('deposit_category_id', 'finance_deposits_deposit_category_id_foreign')->references('id')->on('deposit_categories');
        });
    }

    public function down(): void
    {
        Schema::table('finance_deposits', function (Blueprint $table) {
            $table->dropForeign('finance_deposits_deposit_category_id_foreign');
            $table->dropColumn('deposit_category_id');
            $table->string('category', 191)->after('amount');
        });
    }
};
