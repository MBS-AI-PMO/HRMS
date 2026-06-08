<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('team_name');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('project_manager_id');
            $table->unsignedBigInteger('assistant_hr_id')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('added_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'team_name']);
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('employee_id');
            $table->timestamps();

            $table->unique(['team_id', 'employee_id']);
        });

        $permissions = [
            ['id' => 298, 'name' => 'team-management', 'guard_name' => 'web'],
            ['id' => 299, 'name' => 'view-team', 'guard_name' => 'web'],
            ['id' => 300, 'name' => 'store-team', 'guard_name' => 'web'],
            ['id' => 301, 'name' => 'edit-team', 'guard_name' => 'web'],
            ['id' => 302, 'name' => 'delete-team', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            if (! DB::table('permissions')->where('id', $permission['id'])->exists()) {
                DB::table('permissions')->insert($permission);
            }
        }

        foreach ([298, 299, 300, 301, 302] as $permissionId) {
            if (! DB::table('role_has_permissions')->where('role_id', 1)->where('permission_id', $permissionId)->exists()) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => 1,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');

        DB::table('role_has_permissions')->whereIn('permission_id', [298, 299, 300, 301, 302])->delete();
        DB::table('permissions')->whereIn('id', [298, 299, 300, 301, 302])->delete();
    }
};
