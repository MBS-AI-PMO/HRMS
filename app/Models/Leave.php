<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Leave extends Model
{
	protected $fillable = [
		'leave_type_id','company_id','department_id','employee_id','start_date','end_date',
		'leave_reason','remarks','status','hr_approval_status','manager_approval_status',
		'approved_by','is_notify','total_days',
	];

	public function company(){
		return $this->hasOne('App\Models\Company','id','company_id');
	}

	public function department(){
		return $this->hasOne('App\Models\Department','id','department_id');
	}

	public function LeaveType(){
		return $this->hasOne('App\Models\LeaveType','id','leave_type_id');
	}

	public function employee(){
		return $this->hasOne('App\Models\Employee','id','employee_id');
	}

	public function approvedByUser(){
		return $this->belongsTo(User::class, 'approved_by');
	}

	public function approvedByEmployee(){
		return $this->belongsTo(Employee::class, 'approved_by');
	}

	public function approvedByName(): string
	{
		if (! $this->approved_by) {
			return '';
		}

		if ($this->approvedByUser) {
			return trim(($this->approvedByUser->first_name ?? '').' '.($this->approvedByUser->last_name ?? ''));
		}

		return $this->approvedByEmployee->full_name ?? '';
	}

	// public function employeeLeaveTypeDetail(){
	// 	return $this->hasOne('App\Models\EmployeeLeaveTypeDetail','employee_id','employee_id');
	// }

	public function setStartDateAttribute($value)
	{
		$this->attributes['start_date'] = Carbon::createFromFormat(config('variable.date_format', 'd-m-Y'), $value)->format('Y-m-d');
	}

	public function getStartDateAttribute($value)
	{
		return Carbon::parse($value)->format(config('variable.date_format', 'd-m-Y'));
	}

	public function setEndDateAttribute($value)
	{
		$this->attributes['end_date'] = Carbon::createFromFormat(config('variable.date_format', 'd-m-Y'), $value)->format('Y-m-d');
	}

	public function getEndDateAttribute($value)
	{
		return Carbon::parse($value)->format(config('variable.date_format', 'd-m-Y'));
	}

	public function getCreatedAtAttribute($value)
	{
		return Carbon::parse($value)->format(config('variable.date_format', 'd-m-Y').' H:i');
	}


}
