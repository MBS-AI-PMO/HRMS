<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $names = [
            'project-category',
            'view-project-category',
            'store-project-category',
            'edit-project-category',
            'delete-project-category',
        ];

        foreach ($names as $name) {
            if (! DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->exists()) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $admin = Role::query()->where('name', 'admin')->where('guard_name', 'web')->first();

        if ($admin) {
            $admin->givePermissionTo($names);
        }
    }

    public function down(): void
    {
        Permission::query()->whereIn('name', [
            'project-category',
            'view-project-category',
            'store-project-category',
            'edit-project-category',
            'delete-project-category',
        ])->where('guard_name', 'web')->delete();
    }
};
