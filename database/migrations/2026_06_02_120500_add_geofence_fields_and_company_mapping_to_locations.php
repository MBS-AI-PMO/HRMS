<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('locations')) {
            Schema::table('locations', function (Blueprint $table) {
                if (! Schema::hasColumn('locations', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('zip');
                }
                if (! Schema::hasColumn('locations', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                }
                if (! Schema::hasColumn('locations', 'max_radius')) {
                    $table->decimal('max_radius', 8, 2)->nullable()->after('longitude');
                }
            });
        }

        if (! Schema::hasTable('company_location')) {
            Schema::create('company_location', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('location_id');
                $table->timestamps();

                $table->unique(['company_id', 'location_id']);
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company_location')) {
            Schema::dropIfExists('company_location');
        }

        if (Schema::hasTable('locations')) {
            Schema::table('locations', function (Blueprint $table) {
                if (Schema::hasColumn('locations', 'max_radius')) {
                    $table->dropColumn('max_radius');
                }
                if (Schema::hasColumn('locations', 'longitude')) {
                    $table->dropColumn('longitude');
                }
                if (Schema::hasColumn('locations', 'latitude')) {
                    $table->dropColumn('latitude');
                }
            });
        }
    }
};

