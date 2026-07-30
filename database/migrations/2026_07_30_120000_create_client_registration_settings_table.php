<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_registration_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('registration_slug')->unique();
            $table->string('label')->nullable();
            $table->json('project_ids')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->string('page_title')->nullable();
            $table->text('intro_text')->nullable();
            $table->text('success_message')->nullable();
            $table->boolean('allow_department_selection')->default(true);
            $table->boolean('allow_designation_selection')->default(true);
            $table->boolean('allow_shift_selection')->default(false);
            $table->unsignedBigInteger('default_department_id')->nullable();
            $table->unsignedBigInteger('default_designation_id')->nullable();
            $table->unsignedBigInteger('default_office_shift_id')->nullable();
            $table->unsignedBigInteger('default_role_users_id')->default(3);
            $table->string('default_attendance_type')->default('location_based');
            $table->boolean('auto_approve')->default(false);
            $table->json('form_fields')->nullable();
            $table->timestamps();

            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_registration_settings');
    }
};
