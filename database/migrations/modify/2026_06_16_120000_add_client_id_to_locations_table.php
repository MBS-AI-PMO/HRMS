<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('locations')) {
            return;
        }

        Schema::table('locations', function (Blueprint $table) {
            if (! Schema::hasColumn('locations', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('location_name');
                $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('locations') || ! Schema::hasColumn('locations', 'client_id')) {
            return;
        }

        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
