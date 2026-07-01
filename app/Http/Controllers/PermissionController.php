<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class PermissionController extends Controller {

	public function set_permission(Request $request)
	{
		if (! auth()->user()->can('set-permission')) {
			return response()->json(['error' => __('You are not authorized')], 403);
		}

		try {
			$role = Role::findById((int) $request->input('roleId'));
			$requested = array_values(array_unique(array_filter((array) $request->input('checkedId', []), function ($name) {
				return is_string($name) && trim($name) !== '';
			})));

			$this->ensureDefaultPermissionsExist();
			$this->ensurePermissionsExist($requested);

			$registrar = app(\Spatie\Permission\PermissionRegistrar::class);
			$registrar->forgetCachedPermissions();

			$permissions = Permission::query()
				->where('guard_name', 'web')
				->whereIn('name', $requested)
				->get();

			$missing = array_diff($requested, $permissions->pluck('name')->all());

			// Sync models (not name strings) so Spatie does not rely on a stale permission cache.
			$role->syncPermissions($permissions);

			$registrar->forgetCachedPermissions();

			$message = __('Successfully saved the permission');
			if ($missing !== []) {
				$message .= ' '.__('Skipped unknown permissions: ').implode(', ', $missing);
			}

			return response()->json(['success' => $message]);
		} catch (Throwable $e) {
			report($e);

			return response()->json([
				'error' => __('Could not save permissions. Run migrations or add team permissions in database.').' '.$e->getMessage(),
			], 500);
		}
	}

	protected function ensurePermissionsExist(array $names): void
	{
		$now = now();

		foreach ($names as $name) {
			if (! is_string($name) || trim($name) === '') {
				continue;
			}

			$name = trim($name);

			$exists = DB::table('permissions')
				->where('name', $name)
				->where('guard_name', 'web')
				->exists();

			if ($exists) {
				continue;
			}

			DB::table('permissions')->insert([
				'name' => $name,
				'guard_name' => 'web',
				'created_at' => $now,
				'updated_at' => $now,
			]);
		}
	}

	protected function ensureDefaultPermissionsExist(): void
	{
		$this->ensurePermissionsExist([
			'team-management',
			'view-team',
			'store-team',
			'edit-team',
			'delete-team',
			'organization',
			'location-head-access',
			'view-my-team',
			'view-my-locations',
			'scoped-view-employees',
			'scoped-view-employee-details',
			'scoped-manage-leave',
			'location-head-reports',
			'report-clock-in-locations',
			'daily-attendances',
			'date-wise-attendances',
			'monthly-attendances',
		]);
	}

	protected function ensureTeamManagementPermissionsExist(): void
	{
		$this->ensureDefaultPermissionsExist();
	}

	public function rolePermission($id)
    {
		if (auth()->user()->can('set-permission')) {

            //Remove This Part Later
            // DB::table('permissions')
            // ->updateOrInsert(
            //     ['name' => 'report-pension'],
            //     ['guard_name' => 'web']
            // );
            // //Remove This Part Later

			$role = Role::findById($id);

			return view('settings.roles.permission',compact('role'));
		}
		return response()->json(['success' => __('You are not authorized')]);
	}

	public function permissionDetails($id)
	{
		$role = Role::findById($id);
		$role_permissions = $role->permissions()->select('name')->get();
        //return response($role_permissions);

		$permissions = array();
		foreach ($role_permissions as $permission)
		{
			$permissions[] = $permission->name;
		}
		return json_encode($permissions);
	}
}
