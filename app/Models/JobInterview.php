<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class JobInterview extends Model
{
    //

	protected $guarded=[];

	public function InterviewJob(){
		return $this->belongsTo('App\Models\JobPost','job_id','id');
	}

	public function User(){
		return $this->belongsTo('App\Models\User','added_by','id');
	}

	public function employees(){
		return $this->belongsToMany(Employee::class,'employee_interview','interview_id','employee_id');
	}

	public function candidates(){
		return $this->belongsToMany(JobCandidate::class,'candidate_interview','interview_id','candidate_id');
	}

	public function setInterviewDateAttribute($value)
	{
		$this->attributes['interview_date'] = Carbon::createFromFormat(config('variable.date_format', 'd-m-Y'), $value)->format('Y-m-d');
	}

	public function getInterviewDateAttribute($value)
	{
		return Carbon::parse($value)->format(config('variable.date_format', 'd-m-Y'));
	}
}
