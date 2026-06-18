<?php

namespace App\Http\Controllers;

use App\Models\company;
use App\Models\office_shift;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class OfficeShiftController extends Controller {

	protected function shiftDayKeys(): array
	{
		return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
	}

	protected function buildShiftFormState(?office_shift $shift = null): array
	{
		$workingDays = [];
		$dayTimes = [];

		foreach ($this->shiftDayKeys() as $day) {
			$in = $shift ? $shift->{$day.'_in'} : null;
			$out = $shift ? $shift->{$day.'_out'} : null;
			$dayTimes[$day] = ['in' => $in, 'out' => $out];

			if ($in || $out) {
				$workingDays[] = $day;
			}
		}

		if ($shift === null && empty($workingDays)) {
			$workingDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
		}

		$timingMode = 'same';
		$commonIn = '';
		$commonOut = '';

		if (! empty($workingDays)) {
			$timePairs = [];

			foreach ($workingDays as $day) {
				$timePairs[] = trim((string) ($dayTimes[$day]['in'] ?? '')).'|'.trim((string) ($dayTimes[$day]['out'] ?? ''));
			}

			$timingMode = count(array_unique($timePairs)) === 1 ? 'same' : 'different';
			$commonIn = $dayTimes[$workingDays[0]]['in'] ?? '';
			$commonOut = $dayTimes[$workingDays[0]]['out'] ?? '';
		}

		return [
			'workingDays' => $workingDays,
			'dayTimes' => $dayTimes,
			'timingMode' => $timingMode,
			'commonIn' => $commonIn,
			'commonOut' => $commonOut,
		];
	}

	protected function normalizeShiftTiming(Request $request): array
	{
		$allowedDays = $this->shiftDayKeys();
		$workingDays = array_values(array_intersect(
			$allowedDays,
			array_map('strtolower', (array) $request->input('working_days', []))
		));
		$timingMode = $request->input('timing_mode', 'same') === 'different' ? 'different' : 'same';
		$errors = [];

		if (empty($workingDays)) {
			$errors[] = __('Please select at least one working day.');
		}

		$data = [];

		foreach ($allowedDays as $day) {
			$data[$day.'_in'] = null;
			$data[$day.'_out'] = null;
		}

		if ($timingMode === 'same') {
			$commonIn = trim((string) $request->input('common_in', ''));
			$commonOut = trim((string) $request->input('common_out', ''));

			if ($commonIn === '' || $commonOut === '') {
				$errors[] = __('Please enter in and out time for the selected working days.');
			} else {
				foreach ($workingDays as $day) {
					$data[$day.'_in'] = $commonIn;
					$data[$day.'_out'] = $commonOut;
				}
			}
		} else {
			foreach ($workingDays as $day) {
				$in = trim((string) $request->input($day.'_in', ''));
				$out = trim((string) $request->input($day.'_out', ''));

				if ($in === '' || $out === '') {
					$errors[] = __('Please enter in and out time for each selected working day.');
					break;
				}

				$data[$day.'_in'] = $in;
				$data[$day.'_out'] = $out;
			}
		}

		if (! empty($errors)) {
			return ['errors' => array_values(array_unique($errors))];
		}

		return ['data' => $data];
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$logged_user = auth()->user();
		$companies = company::select('id', 'company_name')->get();

		if ($logged_user->can('view-office_shift'))
		{
			if (request()->ajax())
			{
				return datatables()->of(office_shift::with('company')->get())
					->setRowId(function ($office_shift)
					{
						return $office_shift->id;
					})
					->addColumn('company', function ($row)
					{
						return $row->company->company_name ?? ' ';
					})
					->addColumn('action', function ($data)
					{
						$button = '';
						if (auth()->user()->can('edit-office_shift'))
						{
							$button = '<a id="' . $data->id . '" class="edit btn btn-primary btn-sm" href="' . route('office_shift.edit', $data->id) . '"><i class="dripicons-pencil"></i></a>';
							$button .= '&nbsp;&nbsp;';
						}
						if (auth()->user()->can('delete-office_shift'))
						{
							$button .= '<button type="button" name="delete" id="' . $data->id . '" class="delete btn btn-danger btn-sm"><i class="dripicons-trash"></i></button>';
						}

						return $button;
					})
					->rawColumns(['action'])
					->make(true);
			}

			return view('timesheet.office_shift.index', compact('companies'));
		}

		return abort('403', __('You are not authorized'));
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		//
		$logged_user = auth()->user();
		$companies = company::select('id', 'company_name')->get();

		if ($logged_user->can('store-office_shift'))
		{
			$shiftFormState = $this->buildShiftFormState();

			return view('timesheet.office_shift.create', compact('companies', 'shiftFormState'));
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

		if ($logged_user->can('store-office_shift'))
		{
			$validator = Validator::make($request->only('shift_name', 'company_id'), [
				'shift_name' => 'required',
				'company_id' => 'required|exists:companies,id',
			]);

			if ($validator->fails())
			{
				return response()->json(['errors' => $validator->errors()->all()]);
			}

			$timing = $this->normalizeShiftTiming($request);

			if (isset($timing['errors'])) {
				return response()->json(['errors' => $timing['errors']]);
			}

			$data = array_merge($timing['data'], [
				'shift_name' => $request->shift_name,
				'company_id' => $request->company_id,
			]);

			office_shift::create($data);

			return response()->json(['success' => __('Data Added successfully.')]);
		}

		return response()->json(['success' => __('You are not authorized')]);
	}


	/**
	 * Display the specified resource.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function show($id)
	{

	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function edit($id)
	{
		$logged_user = auth()->user();

		if ($logged_user->can('edit-office_shift'))
		{
			$office_shift = office_shift::findOrFail($id);
			$company_name = $office_shift->company->company_name ?? '';
			$companies = company::select('id', 'company_name')->get();
			$shiftFormState = $this->buildShiftFormState($office_shift);

			return view('timesheet.office_shift.edit', compact('office_shift', 'company_name', 'companies', 'shiftFormState'));
		}
		return response()->json(['success' => __('You are not authorized')]);
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param Request $request
	 * @param int $id
	 * @return Response
	 */
	public function update(Request $request)
	{
		$logged_user = auth()->user();

		if ($logged_user->can('edit-office_shift'))
		{
			$id = $request->hidden_id;

			$validator = Validator::make($request->only('shift_name', 'company_id'), [
				'shift_name' => 'required',
				'company_id' => 'required|exists:companies,id',
			]);

			if ($validator->fails())
			{
				return response()->json(['errors' => $validator->errors()->all()]);
			}

			$timing = $this->normalizeShiftTiming($request);

			if (isset($timing['errors'])) {
				return response()->json(['errors' => $timing['errors']]);
			}

			$data = array_merge($timing['data'], [
				'shift_name' => $request->shift_name,
				'company_id' => $request->company_id,
			]);

			office_shift::whereId($id)->update($data);

			return response()->json(['success' => __('Data is successfully updated')]);
		}

		return response()->json(['success' => __('You are not authorized')]);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function destroy($id)
	{
		if(!env('USER_VERIFIED'))
		{
			return response()->json(['error' => 'This feature is disabled for demo!']);
		}
		$logged_user = auth()->user();

		if ($logged_user->can('delete-office_shift'))
		{
			office_shift::whereId($id)->delete();

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

		if ($logged_user->can('delete-office_shift'))
		{

			$office_shift_id = $request['officeShiftIdArray'];
			$office_shift = office_shift::whereIntegerInRaw('id', $office_shift_id);
			if ($office_shift->delete())
			{
				return response()->json(['success' => __('Multi Delete', ['key' => __('Office Shift')])]);
			} else
			{
				return response()->json(['error' => 'Error,selected shifts can not be deleted']);
			}
		}

		return response()->json(['success' => __('You are not authorized')]);
	}


}
