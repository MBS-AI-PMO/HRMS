<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class location extends Model
{
	protected $fillable = [
		'location_name', 'location_head', 'address1','address2','city','state','country','zip',
        'latitude', 'longitude', 'max_radius',
	];

	public function country(){
		return $this->hasOne('App\Models\Country','id','country');
	}

	public function LocationHead(){
		return $this->hasOne('App\Models\Employee','id','location_head');
	}

    public function companies()
    {
        return $this->belongsToMany(company::class, 'company_location', 'location_id', 'company_id');
    }


}
