<?php

namespace App\Models;

use App\Services\ProjectTimelineService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
	protected $fillable = [
		'title','client_id','project_category_id','company_id','department_id','start_date','end_date','project_priority','description','summary','total_revenue',
		'project_status','project_note','is_notify','added_by','project_progress'
	];

	public function company(){
		return $this->hasOne('App\Models\company','id','company_id');
	}
	public function department(){
		return $this->belongsTo(department::class, 'department_id');
	}
	public function client(){
		return $this->hasOne('App\Models\Client','id','client_id');
	}
	public function projectCategory(){
		return $this->belongsTo(ProjectCategory::class, 'project_category_id');
	}
	public function addedBy(){
		return $this->hasOne('App\Models\User','id','added_by');
	}
	public function assignedEmployees(){
		return $this->belongsToMany(Employee::class)->withPivot('is_lead');
	}

	public function leads(){
		return $this->belongsToMany(Employee::class)->withPivot('is_lead')->wherePivot('is_lead', 1);
	}

	public function members(){
		return $this->belongsToMany(Employee::class)->withPivot('is_lead')->wherePivot('is_lead', 0);
	}

	/**
	 * Project ids where the given user is assigned as a lead (is_lead = 1).
	 */
	public static function projectIdsLedBy(int $userId): array
	{
		return \Illuminate\Support\Facades\DB::table('employee_project')
			->where('employee_id', $userId)
			->where('is_lead', 1)
			->pluck('project_id')
			->map(fn ($id) => (int) $id)
			->unique()
			->values()
			->all();
	}

	public static function userLeadsAnyProject(int $userId): bool
	{
		return \Illuminate\Support\Facades\DB::table('employee_project')
			->where('employee_id', $userId)
			->where('is_lead', 1)
			->exists();
	}

	/**
	 * Employee ids that are members (is_lead = 0) of any project led by the given user.
	 */
	public static function memberEmployeeIdsLedBy(int $userId): array
	{
		$projectIds = static::projectIdsLedBy($userId);

		if ($projectIds === []) {
			return [];
		}

		return \Illuminate\Support\Facades\DB::table('employee_project')
			->whereIn('project_id', $projectIds)
			->where('is_lead', 0)
			->pluck('employee_id')
			->map(fn ($id) => (int) $id)
			->unique()
			->values()
			->all();
	}

	/**
	 * Set the leads (is_lead = 1) for this project without touching member rows (is_lead = 0).
	 */
	public function syncLeads(array $leadIds): void
	{
		$leadIds = collect($leadIds)
			->filter()
			->map(fn ($id) => (int) $id)
			->unique()
			->values()
			->all();

		$stale = \Illuminate\Support\Facades\DB::table('employee_project')
			->where('project_id', $this->id)
			->where('is_lead', 1);

		if ($leadIds !== []) {
			$stale->whereNotIn('employee_id', $leadIds);
		}

		$stale->delete();

		foreach ($leadIds as $employeeId) {
			\Illuminate\Support\Facades\DB::table('employee_project')->updateOrInsert(
				['project_id' => $this->id, 'employee_id' => $employeeId],
				['is_lead' => 1]
			);
		}
	}

	protected static function booted(): void
	{
		static::creating(function (Project $project) {
			$status = strtolower(trim((string) ($project->project_status ?? '')));

			if ($status === '' || in_array($status, ['not_started', 'not started'], true)) {
				$project->project_status = 'in_progress';
			}
		});

		static::saving(function (Project $project) {
			app(ProjectTimelineService::class)->apply($project);
		});
	}

	public function setStartDateAttribute($value)
	{
		$this->attributes['start_date'] = Carbon::createFromFormat(env('Date_Format'), $value)->format('Y-m-d');
	}

	public function getStartDateAttribute($value)
	{
		return Carbon::parse($value)->format(env('Date_Format'));
	}

	public function setEndDateAttribute($value)
	{
		if ($value === null || $value === '') {
			$this->attributes['end_date'] = null;

			return;
		}

		$this->attributes['end_date'] = Carbon::createFromFormat(env('Date_Format'), $value)->format('Y-m-d');
	}

	public function getEndDateAttribute($value)
	{
		if ($value === null || $value === '') {
			return null;
		}

		return Carbon::parse($value)->format(env('Date_Format'));
	}

}
