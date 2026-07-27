<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appraisal extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'department_id',
        'designation_id',
        'customer_experience',
        'marketing',
        'administration',
        'professionalism',
        'integrity',
        'attendance',
        'remarks',
        'date',
    ];

    public function company(){
      return $this->hasOne('App\Models\Company','id','company_id');
    }

    public function employee(){
      return $this->hasOne('App\Models\Employee','id','employee_id');
    }

    public function department(){
		  return $this->hasOne('App\Models\Department','id','department_id');
	  }

    public function designation(){
		  return $this->hasOne('App\Models\Designation','id','designation_id');
    }
}
