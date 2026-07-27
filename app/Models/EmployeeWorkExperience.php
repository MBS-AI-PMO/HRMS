<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class EmployeeWorkExperience extends Model
{
	protected $guarded=[];

	protected $table ='employee_work_experience';

	public function employee(){
		return $this->hasOne('App\Models\Employee','id','employee_id');
	}

	public function setFromYearAttribute($value)
	{
		$this->attributes['from_year'] = Carbon::createFromFormat(config('variable.date_format', 'd-m-Y'), $value)->format('Y-m-d');
	}

	public function getFromYearAttribute($value)
	{
		return Carbon::parse($value)->format(config('variable.date_format', 'd-m-Y'));
	}

	public function setToYearAttribute($value)
	{
		$this->attributes['to_year'] = Carbon::createFromFormat(config('variable.date_format', 'd-m-Y'), $value)->format('Y-m-d');
	}

	public function getToYearAttribute($value)
	{
		return Carbon::parse($value)->format(config('variable.date_format', 'd-m-Y'));
	}
}
