<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        if (! Schema::hasColumn('employees', 'cnic')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('cnic', 20)->nullable()->unique()->after('contact_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'cnic')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropUnique(['cnic']);
                $table->dropColumn('cnic');
            });
        }
    }
};
