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
			$requested = array_values(array_filter((array) $request->input('checkedId', [])));

			$this->ensureTeamManagementPermissionsExist();

			$existing = Permission::query()
				->whereIn('name', $requested)
				->pluck('name')
				->all();

			$missing = array_diff($requested, $existing);

			$role->syncPermissions($existing);

			app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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

	protected function ensureTeamManagementPermissionsExist(): void
	{
		foreach ([
			'team-management',
			'view-team',
			'store-team',
			'edit-team',
			'delete-team',
			'organization',
		] as $name) {
			Permission::query()->firstOrCreate(
				['name' => $name, 'guard_name' => 'web']
			);
		}
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
