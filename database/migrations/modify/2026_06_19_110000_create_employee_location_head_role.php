<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'location-head-access',
            'view-my-team',
            'view-my-locations',
            'scoped-view-employees',
            'scoped-view-employee-details',
            'scoped-manage-leave',
            'location-head-reports',
            'daily-attendances',
            'date-wise-attendances',
            'monthly-attendances',
            'report-clock-in-locations',
        ];

        foreach ($permissions as $name) {
            if (! DB::table('permissions')->where('name', $name)->exists()) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $employeeRole = Role::query()->where('name', 'employee')->first();

        if ($employeeRole) {
            $permissionNames = Permission::query()
                ->whereIn('name', $permissions)
                ->pluck('name')
                ->all();

            foreach ($permissionNames as $permissionName) {
                if (! $employeeRole->hasPermissionTo($permissionName)) {
                    $employeeRole->givePermissionTo($permissionName);
                }
            }
        }
    }

    public function down(): void
    {
        $employeeRole = Role::query()->where('name', 'employee')->first();

        if ($employeeRole) {
            $employeeRole->revokePermissionTo([
                'location-head-access',
                'view-my-team',
                'view-my-locations',
                'scoped-view-employees',
                'scoped-view-employee-details',
                'scoped-manage-leave',
                'location-head-reports',
                'report-clock-in-locations',
            ]);
        }

        DB::table('permissions')->whereIn('name', [
            'view-my-team',
            'scoped-view-employee-details',
            'location-head-reports',
        ])->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
