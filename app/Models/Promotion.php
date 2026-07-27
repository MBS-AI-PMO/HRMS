<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
	protected $fillable = [
		'employee_id', 'company_id', 'promotion_title','description','promotion_date'
	];

	public function company(){
		return $this->hasOne('App\Models\Company','id','company_id');
	}

	public function employee(){
		return $this->hasOne('App\Models\Employee','id','employee_id');
	}

	public function setPromotionDateAttribute($value)
	{
		$this->attributes['promotion_date'] = Carbon::createFromFormat(config('variable.date_format', 'd-m-Y'), $value)->format('Y-m-d');
	}

	public function getPromotionDateAttribute($value)
	{
		return Carbon::parse($value)->format(config('variable.date_format', 'd-m-Y'));
	}
}
