<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
	protected $fillable = [
		'event_title','company_id','department_id','event_note','event_date','event_time',
		'status','is_notify'
	];

	public function company(){
		return $this->hasOne('App\Models\Company','id','company_id');
	}

	public function department(){
		return $this->hasOne('App\Models\Department','id','department_id');
	}

	public function setEventDateAttribute($value)
	{
		$this->attributes['event_date'] = Carbon::createFromFormat(config('variable.date_format', 'd-m-Y'), $value)->format('Y-m-d');
	}

	public function getEventDateAttribute($value)
	{
		return Carbon::parse($value)->format(config('variable.date_format', 'd-m-Y'));
	}

}
