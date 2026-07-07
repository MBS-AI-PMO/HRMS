<?php

namespace App\Http\Controllers;

use App\Models\ProjectCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProjectCategoryController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('view-project-category')) {
            abort(403, __('You are not authorized'));
        }

        if (request()->ajax()) {
            return datatables()->eloquent(
                ProjectCategory::query()->latest('id')
            )
                ->addColumn('status', function (ProjectCategory $row) {
                    return $row->is_active ? __('Active') : __('Inactive');
                })
                ->addColumn('description', function (ProjectCategory $row) {
                    return $row->description ?: '—';
                })
                ->addColumn('action', function (ProjectCategory $row) {
                    return $this->actionButtons($row->id);
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('projects.project_category.index', [
            'clients' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('store-project-category')) {
            return response()->json(['errors' => [__('You are not authorized')]], 403);
        }

        $validator = Validator::make($request->all(), $this->rules($request));

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()]);
        }

        ProjectCategory::query()->create($this->payloadFromRequest($request));

        return response()->json(['success' => __('Data Added successfully.')]);
    }

    public function edit($id)
    {
        if (! request()->ajax()) {
            abort(404);
        }

        $category = ProjectCategory::query()->findOrFail($id);

        return response()->json(['data' => $category]);
    }

    public function update(Request $request)
    {
        if (! auth()->user()->can('edit-project-category')) {
            return response()->json(['errors' => [__('You are not authorized')]], 403);
        }

        $id = (int) $request->input('hidden_id');
        $category = ProjectCategory::query()->findOrFail($id);

        $validator = Validator::make($request->all(), $this->rules($request, $id));

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()]);
        }

        $category->update($this->payloadFromRequest($request));

        return response()->json(['success' => __('Data is successfully updated')]);
    }

    public function destroy($id)
    {
        if (! env('USER_VERIFIED')) {
            return response()->json(['error' => 'This feature is disabled for demo!']);
        }

        if (! auth()->user()->can('delete-project-category')) {
            return response()->json(['error' => __('You are not authorized')], 403);
        }

        $category = ProjectCategory::query()->findOrFail($id);

        if ($category->projects()->exists()) {
            return response()->json([
                'error' => __('Cannot delete: projects are linked to this category.'),
            ]);
        }

        $category->delete();

        return response()->json(['success' => __('Data is successfully deleted')]);
    }

    public function delete_by_selection(Request $request)
    {
        if (! env('USER_VERIFIED')) {
            return response()->json(['error' => 'This feature is disabled for demo!']);
        }

        if (! auth()->user()->can('delete-project-category')) {
            return response()->json(['error' => __('You are not authorized')], 403);
        }

        foreach ((array) $request->input('categoryIdArray', []) as $id) {
            $category = ProjectCategory::query()->find((int) $id);

            if (! $category) {
                continue;
            }

            if ($category->projects()->exists()) {
                return response()->json([
                    'error' => __('Cannot delete category ":name" because projects are linked to it.', [
                        'name' => $category->category_name,
                    ]),
                ]);
            }

            $category->delete();
        }

        return response()->json(['success' => __('Data is successfully deleted')]);
    }

    protected function rules(Request $request, ?int $ignoreId = null): array
    {
        return [
            'category_name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('project_categories', 'category_name')->ignore($ignoreId),
            ],
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ];
    }

    protected function payloadFromRequest(Request $request): array
    {
        return [
            'category_name' => trim((string) $request->category_name),
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    protected function actionButtons(int $id): string
    {
        $button = '';

        if (auth()->user()->can('edit-project-category')) {
            $button .= '<button type="button" name="edit" id="'.$id.'" class="edit btn btn-primary btn-sm"><i class="dripicons-pencil"></i></button>&nbsp;&nbsp;';
        }

        if (auth()->user()->can('delete-project-category')) {
            $button .= '<button type="button" name="delete" id="'.$id.'" class="delete btn btn-danger btn-sm"><i class="dripicons-trash"></i></button>';
        }

        return $button;
    }
}
