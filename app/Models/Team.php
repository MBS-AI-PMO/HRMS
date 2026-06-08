<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'company_id',
        'team_name',
        'department_id',
        'project_manager_id',
        'assistant_hr_id',
        'description',
        'is_active',
        'added_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(company::class, 'company_id');
    }

    public function department()
    {
        return $this->belongsTo(department::class, 'department_id');
    }

    public function projectManager()
    {
        return $this->belongsTo(Employee::class, 'project_manager_id');
    }

    public function assistantHr()
    {
        return $this->belongsTo(Employee::class, 'assistant_hr_id');
    }

    public function members()
    {
        return $this->belongsToMany(Employee::class, 'team_members', 'team_id', 'employee_id')
            ->withTimestamps();
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
