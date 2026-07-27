<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SalaryLoan extends Model
{
	protected $guarded=[];

	public function employee(){
		return $this->hasOne('App\Models\Employee','id','employee_id');
	}

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

}
