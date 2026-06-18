<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('attendances', 'is_overtime')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->boolean('is_overtime')->default(false)->after('overtime');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendances', 'is_overtime')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn('is_overtime');
            });
        }
    }
};
