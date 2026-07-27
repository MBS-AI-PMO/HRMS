<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Resignation extends Model
{
	protected $fillable = [
		'description', 'company_id','department_id','employee_id','resignation_date','notice_date'
	];

	public function company(){
		return $this->hasOne('App\Models\Company','id','company_id');
	}

	public function department(){
		return $this->hasOne('App\Models\Department','id','department_id');
	}

	public function employee(){
		return $this->hasOne('App\Models\Employee','id','employee_id');
	}

	public function setResignationDateAttribute($value)
	{
		$this->attributes['resignation_date'] = Carbon::createFromFormat(config('variable.date_format', 'd-m-Y'), $value)->format('Y-m-d');
	}

	public function getResignationDateAttribute($value)
	{
		return Carbon::parse($value)->format(config('variable.date_format', 'd-m-Y'));
	}

	public function setNoticeDateAttribute($value)
	{
		$this->attributes['notice_date'] = Carbon::createFromFormat(config('variable.date_format', 'd-m-Y'), $value)->format('Y-m-d');
	}

	public function getNoticeDateAttribute($value)
	{
		return Carbon::parse($value)->format(config('variable.date_format', 'd-m-Y'));
	}
}
