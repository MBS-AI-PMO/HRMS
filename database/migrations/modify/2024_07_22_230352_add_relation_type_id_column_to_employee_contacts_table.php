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
        Schema::table('employee_contacts', function (Blueprint $table) {
            $table->dropColumn('relation');
            $table->foreignId('relation_type_id')->after('employee_id')->default(1)
                  ->foreign('relation_type_id', 'employee_contacts_relation_type_id_foreign')->references('id')->on('relation_types');
        });
    }

    public function down(): void
    {
        Schema::table('employee_contacts', function (Blueprint $table) {
            $table->dropForeign('employee_contacts_relation_type_id_foreign');
            $table->dropColumn('relation_type_id');
            $table->string('relation', 191)->after('employee_id');
        });
    }
};
