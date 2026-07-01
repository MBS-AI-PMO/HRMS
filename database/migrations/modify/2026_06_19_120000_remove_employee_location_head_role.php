<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $employeeRole = Role::query()->where('name', 'employee')->first();
        $locationHeadRole = Role::query()->where('name', 'Employee (location-head)')->first();

        if (! $employeeRole || ! $locationHeadRole) {
            return;
        }

        DB::table('users')
            ->where('role_users_id', $locationHeadRole->id)
            ->update(['role_users_id' => $employeeRole->id]);

        if (DB::getSchemaBuilder()->hasTable('employees')) {
            DB::table('employees')
                ->where('role_users_id', $locationHeadRole->id)
                ->update(['role_users_id' => $employeeRole->id]);
        }

        DB::table('model_has_roles')
            ->where('role_id', $locationHeadRole->id)
            ->update(['role_id' => $employeeRole->id]);

        $locationHeadRole->syncPermissions([]);
        DB::table('roles')->where('id', $locationHeadRole->id)->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No rollback — keep a single Employee role.
    }
};
