<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\company;
use App\Models\User;
use App\Support\CompanyScope;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Throwable;

class ClientController extends Controller {

	protected function ensureClientRole(): Role
	{
		$role = Role::query()
			->where('name', 'client')
			->where('guard_name', 'web')
			->first();

		if ($role) {
			if ((int) ($role->is_active ?? 1) !== 1) {
				$role->forceFill(['is_active' => 1])->save();
			}

			return $role;
		}

		if (! Role::query()->whereKey(3)->exists()) {
			return Role::query()->create([
				'id' => 3,
				'name' => 'client',
				'guard_name' => 'web',
				'description' => 'When you create a client, this role and associated.',
				'is_active' => 1,
			]);
		}

		return Role::query()->create([
			'name' => 'client',
			'guard_name' => 'web',
			'description' => 'When you create a client, this role and associated.',
			'is_active' => 1,
		]);
	}

	public function index()
	{
		$logged_user = auth()->user();
		if ($logged_user->can('view-client'))
		{
			if (request()->ajax())
			{
				return datatables()->of(client::latest()->get())
					->setRowId(function ($client)
					{
						return $client->id;
					})
					->addColumn('name', function ($data)
					{
						$label = e(trim($data->first_name.' '.($data->last_name ?? '')));

						return '<a href="'.route('clients.dashboard', $data->id).'" class="font-weight-bold">'.$label.'</a>';
					})
					->addColumn('action', function ($data)
					{
						$button = '';
						if (auth()->user()->can('edit-client'))
						{
							$button .= '<button type="button" name="edit" id="' . $data->id . '" class="edit btn btn-primary btn-sm"><i class="dripicons-pencil"></i></button>';
							$button .= '&nbsp;&nbsp;';
						}
						if (auth()->user()->can('delete-client'))
						{
							$button .= '<button type="button" name="delete" id="' . $data->id . '" class="delete btn btn-danger btn-sm"><i class="dripicons-trash"></i></button>';
						}
						return $button;
					})
					->rawColumns(['action', 'name'])
					->make(true);
			}

			return view('projects.client.index', [
				'companies' => CompanyScope::companiesForSelect(),
				'countries' => DB::table('countries')->select('id', 'name')->get(),
			]);
		}
		return abort('403', __('You are not authorized'));
	}

	protected function resolveCompanyNameFromId(?int $companyId): ?string
	{
		if (! $companyId) {
			return null;
		}

		return company::query()->whereKey($companyId)->value('company_name');
	}

	protected function resolveCompanyIdFromName(?string $companyName): ?int
	{
		$companyName = trim((string) $companyName);

		if ($companyName === '') {
			return null;
		}

		$id = company::query()
			->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower($companyName)])
			->value('id');

		return $id ? (int) $id : null;
	}

	public function store(Request $request)
	{
		$logged_user = auth()->user();
		if ($logged_user->can('store-client'))
		{
			$validator = Validator::make($request->only('username', 'company_id', 'first_name', 'last_name', 'password', 'email'), [
				'username' => 'required|unique:users',
				'email' => 'required|email|unique:users',
				'company_id' => 'required|exists:companies,id',
				'first_name' => 'required',
				'last_name' => 'nullable|string|max:191',
				'password' => 'required|min:4',
			]);


			if ($validator->fails())
			{
				return response()->json(['errors' => $validator->errors()->all()]);
			}


			$user_data = [];
			$data = [];

			$user_data['first_name'] = $request->first_name;
			$user_data['last_name'] = trim((string) $request->last_name) ?: null;
			$user_data['username'] = strtolower(trim($request->username));
			$user_data['email'] = strtolower(trim($request->email));
			$user_data['password'] = bcrypt($request->password);
			$user_data['is_active'] = 1;

			$data['first_name'] = $request->first_name;
			$data['last_name'] = trim((string) $request->last_name) ?: '';
			$data['company_name'] = $this->resolveCompanyNameFromId((int) $request->company_id);
			$data['username'] = $user_data['username'];
			$data['email'] = $user_data['email'];
			$data['contact_no'] = '-';
			$data['gender'] = 'Other';
			$data['is_active'] = 1;

			try {
				DB::beginTransaction();

				$clientRole = $this->ensureClientRole();
				$user_data['role_users_id'] = (int) $clientRole->id;

				$user = User::createAccount($user_data);
				$user->syncRoles($clientRole);

				$data['id'] = $user->id;

				client::create($data);

				DB::commit();
			} catch (Throwable $e) {
				DB::rollBack();
				Log::error('Client create failed', [
					'message' => $e->getMessage(),
					'username' => $user_data['username'] ?? null,
				]);

				return response()->json(['errors' => [__('Could not create client. :message', [
					'message' => $e->getMessage(),
				])]]);
			}

			return response()->json(['success' => __('Data Added successfully.')]);
		}
		return response()->json(['success' => __('You are not authorized')]);
	}

	public function edit($id)
	{

		if (request()->ajax())
		{
			$data = client::findOrFail($id);

			return response()->json([
				'data' => $data,
				'company_id' => $this->resolveCompanyIdFromName($data->company_name),
				'login_type' => $data->user->login_type,
			]);
		}
	}

	public function update(Request $request)
	{

		$logged_user = auth()->user();

		if ($logged_user->can('edit-client'))
		{
			$id = $request->hidden_id;
			$client = Client::findOrFail($id);

			$validator = Validator::make($request->only(
				'username', 'company_id', 'first_name', 'last_name', 'email',
				'contact_no', 'website', 'address1', 'address2', 'city', 'state', 'country', 'zip', 'profile_photo'
			), [
				'username' => 'required|unique:users,username,' . $id,
				'email' => 'required|email|unique:users,email,' . $id,
				'company_id' => 'required|exists:companies,id',
				'first_name' => 'required',
				'last_name' => 'nullable|string|max:191',
				'contact_no' => 'nullable|string|max:15',
				'zip' => 'nullable|numeric',
				'profile_photo' => 'nullable|image|max:2048|mimes:jpeg,png,jpg,gif',
			]);


			if ($validator->fails())
			{
				return response()->json(['errors' => $validator->errors()->all()]);
			}


			$user_data = [];
			$data = [];

			$user_data['first_name'] = $request->first_name;
			$user_data['last_name'] = trim((string) $request->last_name) ?: null;
			$user_data['username'] = strtolower(trim($request->username));
			$user_data['email'] = strtolower(trim($request->email));
			$user_data['contact_no'] = trim((string) $request->contact_no) ?: null;
			$user_data['is_active'] = $request->boolean('is_active') ? 1 : 0;

			$photo = $request->profile_photo;
			if ($photo && $photo->isValid()) {
				if ($client->profile) {
					$file_path = public_path('uploads/profile_photos/' . $client->profile);
					if (file_exists($file_path)) {
						unlink($file_path);
					}
				}
				$file_name = preg_replace('/\s+/', '', $user_data['username']) . '_' . time() . '.' . $photo->getClientOriginalExtension();
				$photo->storeAs('profile_photos', $file_name);
				$user_data['profile_photo'] = $file_name;
			}

			$data['first_name'] = $request->first_name;
			$data['last_name'] = trim((string) $request->last_name) ?: '';
			$data['company_name'] = $this->resolveCompanyNameFromId((int) $request->company_id);
			$data['username'] = $user_data['username'];
			$data['email'] = $user_data['email'];
			$data['contact_no'] = trim((string) $request->contact_no) ?: '-';
			$data['website'] = $request->website;
			$data['address1'] = $request->address1;
			$data['address2'] = $request->address2;
			$data['city'] = $request->city;
			$data['state'] = $request->state;
			$data['country'] = $request->country;
			$data['zip'] = $request->zip;
			$data['is_active'] = $request->boolean('is_active') ? 1 : 0;

			if (isset($user_data['profile_photo'])) {
				$data['profile'] = $user_data['profile_photo'];
			}

			try
			{
				User::whereId($id)->update($user_data);

				client::whereId($id)->update($data);
			} catch (Throwable $e)
			{
				Log::error('Client update failed', ['id' => $id, 'message' => $e->getMessage()]);

				return response()->json(['error' => trans('file.Error')]);
			}


			return response()->json(['success' => __('Data is successfully updated')]);

		} else
		{
			return response()->json(['success' => __('You are not authorized')]);
		}


	}


	public function destroy($id)
	{
		if(!env('USER_VERIFIED'))
		{
			return response()->json(['error' => 'This feature is disabled for demo!']);
		}
		$logged_user = auth()->user();

		if ($logged_user->can('delete-client'))
		{
			$client = Client::findOrFail($id);
			$file_path = $client->profile;

			if ($file_path)
			{
				$file_path = public_path('uploads/profile_photos/' . $file_path);
				if (file_exists($file_path))
				{
					unlink($file_path);
				}
			}

			$client->delete();

			User::whereId($id)->delete();

			return response()->json(['success' => __('Data is successfully deleted')]);
		}
		return response()->json(['success' => __('You are not authorized')]);
	}


	public function delete_by_selection(Request $request)
	{
		if(!env('USER_VERIFIED'))
		{
			return response()->json(['error' => 'This feature is disabled for demo!']);
		}
		$logged_user = auth()->user();

		if ($logged_user->can('delete-client'))
		{
			$client_id = $request['clientIdArray'];
			$clients = Client::whereIntegerInRaw('id', $client_id)->get();

			foreach ($clients as $client)
			{
				$file_path = $client->profile;

				if ($file_path)
				{
					$file_path = public_path('uploads/profile_photos/' . $file_path);
					if (file_exists($file_path))
					{
						unlink($file_path);
					}
				}
				$client->delete();
				User::whereId($client->id)->delete();
			}

			return response()->json(['success' => __('Multi Delete', ['key' => trans('file.Client')])]);
		}

		return response()->json(['success' => __('You are not authorized')]);
	}


}
