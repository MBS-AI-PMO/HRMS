<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_registration_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->unique();
            $table->boolean('is_enabled')->default(false);
            $table->string('page_title')->nullable();
            $table->text('intro_text')->nullable();
            $table->text('success_message')->nullable();
            $table->boolean('allow_department_selection')->default(true);
            $table->boolean('allow_designation_selection')->default(true);
            $table->boolean('allow_shift_selection')->default(true);
            $table->unsignedBigInteger('default_department_id')->nullable();
            $table->unsignedBigInteger('default_designation_id')->nullable();
            $table->unsignedBigInteger('default_office_shift_id')->nullable();
            $table->unsignedBigInteger('default_role_users_id')->default(3);
            $table->string('default_attendance_type')->default('location_based');
            $table->boolean('auto_approve')->default(false);
            $table->json('form_fields')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_registration_settings');
    }
};
