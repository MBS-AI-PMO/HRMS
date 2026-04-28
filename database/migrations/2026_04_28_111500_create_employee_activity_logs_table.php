<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->string('action', 120);
            $table->string('description');
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('performed_by');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_activity_logs');
    }
};
