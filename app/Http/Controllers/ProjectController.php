<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\company;
use App\Notifications\ProjectCreatedNotifiaction;
use App\Notifications\ProjectUpdatedNotification;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectTimelineService;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProjectController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$logged_user = auth()->user();
		$clients = Client::query()
			->select('id', 'first_name', 'last_name', 'company_name')
			->orderBy('first_name')
			->get()
			->map(function (Client $client) {
				$client->resolved_company_id = $this->resolveCompanyIdFromClient($client);

				return $client;
			});
		if ($logged_user->can('view-project')){
			if (request()->ajax()){
				app(ProjectTimelineService::class)->syncAll();

				$projects = Project::with(
					'client:id,first_name,last_name',
					'assignedEmployees',
					'projectCategory:id,category_name'
				)->get();

                return datatables()->of($projects)
					->setRowId(function ($project)
					{
						return $project->id;
					})
					->addColumn('summary', function ($row)
					{
						return '<br><h6><a href="' . route('projects.show', $row->id) . '">' . $row->title . '</a></h6>';
					})
					->addColumn('project_category', function ($row)
					{
						return $row->projectCategory->category_name ?? ' ';
					})
					->addColumn('client', function ($row)
					{
                        if ($row->client_id!=NULL) {
                            return $row->client->first_name.' '.$row->client->last_name;
                        }else{
                            return " ";
                        }

					})
					->addColumn('assigned_employee', function ($row)
					{
						$assigned_name = $row->assignedEmployees()->pluck('last_name', 'first_name');
						$collection = [];
						foreach ($assigned_name as $first => $last)
						{
							$full_name = $first . ' ' . $last;
							array_push($collection, $full_name);
						}

						return $collection;
					})
					->addColumn('action', function ($data)
					{
						$button = '<a id="' . $data->id . '" class="show btn btn-success btn-sm" href="' . route('projects.show', $data) . '"><i class="dripicons-preview"></i></a>';
						$button .= '&nbsp;&nbsp;';
						if (auth()->user()->can('user-edit'))
						{
							$button .= '<button type="button" name="edit" id="' . $data->id . '" class="edit btn btn-primary btn-sm"><i class="dripicons-pencil"></i></button>';
							$button .= '&nbsp;&nbsp;';
						}
						if (auth()->user()->can('user-delete'))
						{
							$button .= '<button type="button" name="delete" id="' . $data->id . '" class="delete btn btn-danger btn-sm"><i class="dripicons-trash"></i></button>';
						}

						return $button;
					})
					->rawColumns(['action', 'summary'])
					->make(true);
			}

			return view('projects.project.index', compact('clients'));
		}

		return abort('403', __('You are not authorized'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function store(Request $request)
	{
		$logged_user = auth()->user();
		if ($logged_user->can('store-project'))
		{
			$request->merge([
				'end_date' => $request->filled('end_date') ? $request->end_date : null,
			]);

			$validator = Validator::make($request->only(
				'title', 'client_id', 'project_category_id', 'employee_id', 'description', 'start_date', 'end_date', 'summary', 'total_revenue'
			),
				[
					'title' => 'required',
					'client_id' => 'required|exists:clients,id',
					'project_category_id' => [
						'required',
						Rule::exists('project_categories', 'id')->where('is_active', true),
					],
					'employee_id' => 'required|array|min:1',
					'employee_id.*' => 'exists:employees,id',
					'start_date' => 'required',
					'end_date' => 'nullable|after_or_equal:start_date',
					'total_revenue' => 'nullable|numeric|min:0',
				]
			);


			if ($validator->fails())
			{
				return response()->json(['errors' => $validator->errors()->all()]);
			}


			$data = [];

			$client = Client::query()->findOrFail((int) $request->client_id);

			$data['summary'] = $request->summary;
			$data['title'] = $request->title;
			$data['company_id'] = $this->resolveCompanyIdFromClient($client);
			$data['department_id'] = null;
			$data['client_id'] = $request->client_id;
			$data['project_category_id'] = (int) $request->project_category_id;
			$data ['start_date'] = $request->start_date;
			$data ['end_date'] = $request->end_date;

			$data ['description'] = $request->description;
			$data ['project_priority'] = 'medium';
			$data['total_revenue'] = $request->filled('total_revenue') ? $request->total_revenue : null;
			$data['project_status'] = 'in_progress';

			try {
				$project = Project::create($data);
				$employees = $request->input('employee_id');
				$project->assignedEmployees()->sync($employees);

				try {
					$notificable = User::where('role_users_id', 1)
						->orwhereIntegerInRaw('id', $employees)
						->get();

					Notification::send($notificable, new ProjectCreatedNotifiaction($project));
				} catch (\Throwable $notificationError) {
					Log::warning('Project created but notification failed', [
						'project_id' => $project->id,
						'message' => $notificationError->getMessage(),
					]);
				}

				return response()->json(['success' => __('Data Added successfully.')]);
			} catch (\Throwable $e) {
				Log::error('Project create failed', [
					'message' => $e->getMessage(),
					'title' => $request->title,
				]);

				return response()->json(['errors' => [$e->getMessage()]]);
			}
		}

		return response()->json(['success' => __('You are not authorized')]);

	}

	/**
	 * Display the specified resource.
	 *
	 * @param Project $project
	 * @return Response
	 */
	public function show(Project $project)
	{
		try
		{
			$name = DB::table('employee_project')->where('project_id', $project->id)->pluck('employee_id')->toArray();
		} catch (Exception $e)
		{
			$name = null;
		}

		$logged_user = auth()->user();

		if ($logged_user->can('view-project') || in_array($logged_user->id, $name))
		{
			app(ProjectTimelineService::class)->apply($project);
			$project->saveQuietly();

			$project->load([
				'client:id,first_name,last_name',
				'company:id,company_name',
				'projectCategory:id,category_name',
			]);

			$company_name = $project->company->company_name ?? '';

			$employees = DB::table('employees')->where('company_id', $project->company_id)
				->select('employees.id', DB::raw("CONCAT(employees.first_name,' ',employees.last_name) as full_name"))
				->get();

			return view('projects.project.details', compact('project', 'employees', 'company_name', 'name'));
		}

		return response()->json(['success' => __('You are not authorized')]);
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param Project $project
	 * @return Response
	 */
	public function edit($id)
	{
		if (request()->ajax())
		{
			$data = Project::findOrFail($id);

			return response()->json(['data' => $data]);
		}

	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param Request $request
	 * @param Project $project
	 * @return Response
	 */
	public function update(Request $request)
	{
		$logged_user = auth()->user();
		if ($logged_user->can('edit-project'))
		{
			$id = $request->hidden_id;

			$request->merge([
				'edit_end_date' => $request->filled('edit_end_date') ? $request->edit_end_date : null,
			]);

			$validator = Validator::make($request->only(
				'edit_title', 'edit_client_id', 'edit_project_category_id',
				'edit_description', 'edit_start_date', 'edit_end_date',
				'edit_summary', 'edit_project_status', 'edit_project_progress', 'edit_total_revenue'
			),
				[
					'edit_title' => 'required',
					'edit_client_id' => 'required|exists:clients,id',
					'edit_project_category_id' => [
						'required',
						Rule::exists('project_categories', 'id')->where('is_active', true),
					],
					'edit_start_date' => 'required',
					'edit_end_date' => 'nullable|after_or_equal:edit_start_date',
					'edit_total_revenue' => 'nullable|numeric|min:0',
				]
			);


			if ($validator->fails())
			{
				return response()->json(['errors' => $validator->errors()->all()]);
			}


			$data = [];

			$client = Client::query()->findOrFail((int) $request->edit_client_id);

			$data['summary'] = $request->edit_summary;
			$data['title'] = $request->edit_title;
			$data['client_id'] = $request->edit_client_id;
			$data['company_id'] = $this->resolveCompanyIdFromClient($client);
			$data['department_id'] = null;
			$data['project_category_id'] = (int) $request->edit_project_category_id;
			$data ['start_date'] = $request->edit_start_date;
			$data ['end_date'] = $request->edit_end_date;

			if ($request->edit_description)
			{
				$data ['description'] = $request->edit_description;
			}

			$data['total_revenue'] = $request->filled('edit_total_revenue') ? $request->edit_total_revenue : null;
			$data ['project_status'] = $request->edit_project_status;

			$project = Project::findOrFail($id);
			$project->update($data);

			return response()->json(['success' => __('Data is successfully updated')]);
		}

		return response()->json(['success' => __('You are not authorized')]);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param Project $project
	 * @return Response
	 */
	public function destroy($id)
	{
		if (!env('USER_VERIFIED'))
		{
			return response()->json(['error' => 'This feature is disabled for demo!']);
		}
		$logged_user = auth()->user();

		if ($logged_user->can('delete-project'))
		{
			Project::whereId($id)->delete();

			return response()->json(['success' => __('Data is successfully deleted')]);
		}

		return response()->json(['success' => __('You are not authorized')]);
	}


	public function progressStore(Request $request, Project $project)
	{

		$data = [];
		if ($request->project_priority)
		{
			$data['project_priority'] = $request->project_priority;
		}
		if ($request->project_status)
		{
			$data['project_status'] = $request->project_status;
		}

		$project->update($data);

		$assigned = $project->assignedEmployees()->pluck('id');


		if (sizeof($assigned) == 0)
		{
			$notificable = User::where('role_users_id', 1)
				->orWhere('id', $project->client_id)
				->get();
			Notification::send($notificable, new ProjectUpdatedNotification($project));
		} else
		{
			$notificable = User::where('role_users_id', 1)
				->orwhereIntegerInRaw('id', $assigned)
				->orWhere('id', $project->client_id)
				->get();
			Notification::send($notificable, new ProjectUpdatedNotification($project));
		}

		return response()->json(['success' => __('Data is successfully updated')]);
	}


	public function notesStore(Request $request, Project $project)
	{

		$validator = Validator::make($request->only('project_note'),
			[
				'project_note' => 'required',
			]
		);

		if ($validator->fails())
		{
			return response()->json(['errors' => $validator->errors()->all()]);
		}

		$data = [];

		$data['project_note'] = $request->project_note;

		$project->update($data);

		return response()->json(['success' => __('Data is successfully updated')]);
	}

	protected function resolveCompanyIdFromClient(Client $client): ?int
	{
		$companyName = trim((string) $client->company_name);

		if ($companyName === '') {
			return null;
		}

		$id = company::query()
			->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower($companyName)])
			->value('id');

		return $id ? (int) $id : null;
	}


}
