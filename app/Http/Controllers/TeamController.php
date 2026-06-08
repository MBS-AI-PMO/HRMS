<?php

namespace App\Http\Controllers;

use App\Models\department;
use App\Models\Employee;
use App\Models\Team;
use App\Services\TeamNotifier;
use App\Support\CompanyScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('view-team')) {
            return abort(403, __('You are not authorized'));
        }

        $companies = CompanyScope::companiesForSelect();

        if (request()->ajax()) {
            $teams = Team::with([
                'company:id,company_name',
                'department:id,department_name',
                'projectManager:id,first_name,last_name',
                'assistantHr:id,first_name,last_name',
                'members:id,first_name,last_name',
            ])->latest('id');

            return datatables()->of($teams)
                ->setRowId(function ($row) {
                    return $row->id;
                })
                ->addColumn('company', function ($row) {
                    return $row->company->company_name ?? '';
                })
                ->addColumn('department', function ($row) {
                    return $row->department->department_name ?? '-';
                })
                ->addColumn('project_manager', function ($row) {
                    return $row->projectManager->full_name ?? '';
                })
                ->addColumn('assistant_hr', function ($row) {
                    return $row->assistantHr->full_name ?? '-';
                })
                ->addColumn('members', function ($row) {
                    return $row->members->pluck('full_name')->filter()->implode(', ');
                })
                ->addColumn('action', function ($data) {
                    $button = '';

                    if (auth()->user()->can('edit-team')) {
                        $button .= '<button type="button" name="edit" id="'.$data->id.'" class="edit btn btn-primary btn-sm"><i class="dripicons-pencil"></i></button>&nbsp;&nbsp;';
                    }

                    if (auth()->user()->can('delete-team')) {
                        $button .= '<button type="button" name="delete" id="'.$data->id.'" class="delete btn btn-danger btn-sm"><i class="dripicons-trash"></i></button>';
                    }

                    return $button;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('organization.team.index', compact('companies'));
    }

    public function employeesOptions(Request $request)
    {
        if (! auth()->user()->can('view-team')) {
            return response()->json(['employees' => [], 'departments' => []], 403);
        }

        $companyId = CompanyScope::resolveCompanyIdForInput((int) $request->get('company_id'));

        $employees = Employee::select('id', 'first_name', 'last_name')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->whereNull('exit_date')
            ->orderBy('first_name')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                ];
            });

        $departments = department::select('id', 'department_name')
            ->where('company_id', $companyId)
            ->orderBy('department_name')
            ->get();

        return response()->json([
            'employees' => $employees,
            'departments' => $departments,
        ]);
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('store-team')) {
            return response()->json(['error' => __('You are not authorized')]);
        }

        $companyId = CompanyScope::resolveCompanyIdForInput((int) $request->company_id);

        $validator = Validator::make($request->all(), [
            'team_name' => 'required|string|max:191|unique:teams,team_name,NULL,id,company_id,'.$companyId,
            'company_id' => 'required|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'project_manager_id' => 'required|exists:employees,id',
            'assistant_hr_id' => 'nullable|exists:employees,id|different:project_manager_id',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:employees,id',
            'description' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()]);
        }

        $this->assertEmployeesBelongToCompany([
            $request->project_manager_id,
            $request->assistant_hr_id,
            ...((array) $request->member_ids),
        ], $companyId);

        $team = Team::create([
            'team_name' => $request->team_name,
            'company_id' => $companyId,
            'department_id' => $request->department_id,
            'project_manager_id' => $request->project_manager_id,
            'assistant_hr_id' => $request->assistant_hr_id,
            'description' => $request->description,
            'is_active' => true,
            'added_by' => auth()->id(),
        ]);

        $memberIds = collect($request->member_ids ?? [])
            ->filter()
            ->reject(fn ($id) => (int) $id === (int) $request->project_manager_id || (int) $id === (int) $request->assistant_hr_id)
            ->unique()
            ->values()
            ->all();

        if (! empty($memberIds)) {
            $team->members()->sync($memberIds);
        }

        TeamNotifier::notify($team->fresh(['members']), 'created');

        return response()->json(['success' => __('Team created successfully.')]);
    }

    public function edit($id)
    {
        if (! request()->ajax() || ! auth()->user()->can('edit-team')) {
            return response()->json(['error' => __('You are not authorized')], 403);
        }

        $team = Team::with('members:id')->findOrFail($id);
        CompanyScope::assertCompanyAccess($team->company_id);

        return response()->json([
            'data' => $team,
            'member_ids' => $team->members->pluck('id')->values(),
        ]);
    }

    public function update(Request $request)
    {
        if (! auth()->user()->can('edit-team')) {
            return response()->json(['error' => __('You are not authorized')]);
        }

        $team = Team::findOrFail($request->hidden_id);
        CompanyScope::assertCompanyAccess($team->company_id);

        $companyId = CompanyScope::resolveCompanyIdForInput((int) $request->company_id);

        $validator = Validator::make($request->all(), [
            'team_name' => 'required|string|max:191|unique:teams,team_name,'.$team->id.',id,company_id,'.$companyId,
            'company_id' => 'required|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'project_manager_id' => 'required|exists:employees,id',
            'assistant_hr_id' => 'nullable|exists:employees,id|different:project_manager_id',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:employees,id',
            'description' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()]);
        }

        $this->assertEmployeesBelongToCompany([
            $request->project_manager_id,
            $request->assistant_hr_id,
            ...((array) $request->member_ids),
        ], $companyId);

        $team->update([
            'team_name' => $request->team_name,
            'company_id' => $companyId,
            'department_id' => $request->department_id,
            'project_manager_id' => $request->project_manager_id,
            'assistant_hr_id' => $request->assistant_hr_id,
            'description' => $request->description,
        ]);

        $memberIds = collect($request->member_ids ?? [])
            ->filter()
            ->reject(fn ($id) => (int) $id === (int) $request->project_manager_id || (int) $id === (int) $request->assistant_hr_id)
            ->unique()
            ->values()
            ->all();

        $team->members()->sync($memberIds);

        TeamNotifier::notify($team->fresh(['members']), 'updated');

        return response()->json(['success' => __('Team updated successfully.')]);
    }

    public function destroy($id)
    {
        if (! auth()->user()->can('delete-team')) {
            return response()->json(['error' => __('You are not authorized')]);
        }

        $team = Team::with('members')->findOrFail($id);
        CompanyScope::assertCompanyAccess($team->company_id);

        TeamNotifier::notify($team, 'deleted');
        $team->members()->detach();
        $team->delete();

        return response()->json(['success' => __('Team deleted successfully.')]);
    }

    public function delete_by_selection(Request $request)
    {
        if (! auth()->user()->can('delete-team')) {
            return response()->json(['error' => __('You are not authorized')]);
        }

        $teamIds = $request['teamIdArray'] ?? [];

        if (empty($teamIds)) {
            return response()->json(['error' => __('No team selected.')]);
        }

        foreach ($teamIds as $teamId) {
            $team = Team::with('members')->find($teamId);

            if (! $team) {
                continue;
            }

            CompanyScope::assertCompanyAccess($team->company_id);
            TeamNotifier::notify($team, 'deleted');
            $team->members()->detach();
            $team->delete();
        }

        return response()->json(['success' => __('Selected teams deleted successfully.')]);
    }

    protected function assertEmployeesBelongToCompany(array $employeeIds, int $companyId): void
    {
        $ids = collect($employeeIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return;
        }

        $count = Employee::withoutGlobalScopes()
            ->whereIn('id', $ids)
            ->where('company_id', $companyId)
            ->count();

        if ($count !== $ids->count()) {
            abort(403, __('Selected employees must belong to the same company.'));
        }
    }
}
