<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('location_heads')) {
            Schema::create('location_heads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('location_id');
                $table->unsignedBigInteger('employee_id');
                $table->timestamps();

                $table->unique(['location_id', 'employee_id']);
                $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('locations') && Schema::hasColumn('locations', 'location_head')) {
            $legacyHeads = DB::table('locations')
                ->whereNotNull('location_head')
                ->select('id', 'location_head')
                ->get();

            foreach ($legacyHeads as $row) {
                DB::table('location_heads')->updateOrInsert(
                    [
                        'location_id' => $row->id,
                        'employee_id' => $row->location_head,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('location_heads');
    }
};
