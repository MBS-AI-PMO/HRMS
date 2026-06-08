<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'company_id',
        'team_name',
        'department_id',
        'department_head_id',
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

    public function departmentHead()
    {
        return $this->belongsTo(Employee::class, 'department_head_id');
    }

    public function projectManager()
    {
        return $this->belongsTo(Employee::class, 'project_manager_id');
    }

    public function leaderEmployeeIds(): array
    {
        return array_values(array_filter([
            $this->department_head_id,
            $this->project_manager_id,
            $this->assistant_hr_id,
        ]));
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

    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($builder) use ($userId) {
            $builder->where('department_head_id', $userId)
                ->orWhere('project_manager_id', $userId)
                ->orWhere('assistant_hr_id', $userId)
                ->orWhereHas('members', function ($members) use ($userId) {
                    $members->where('employees.id', $userId);
                });
        });
    }

    public static function userHasTeamAccess(int $userId): bool
    {
        return static::query()->forUser($userId)->exists();
    }

    public function scopeLedByUser($query, int $userId)
    {
        return $query->where(function ($builder) use ($userId) {
            $builder->where('department_head_id', $userId)
                ->orWhere('project_manager_id', $userId)
                ->orWhere('assistant_hr_id', $userId);
        });
    }

    public static function userCanLeadAnyTeam(int $userId): bool
    {
        return static::query()->ledByUser($userId)->exists();
    }

    public function userIsLeader(int $userId): bool
    {
        return in_array($userId, array_map('intval', $this->leaderEmployeeIds()), true);
    }

    public function roleLabelForUser(int $userId): string
    {
        $roles = [];

        if ((int) $this->department_head_id === $userId) {
            $roles[] = __('Department Head');
        }
        if ((int) $this->project_manager_id === $userId) {
            $roles[] = __('Project Manager');
        }
        if ((int) $this->assistant_hr_id === $userId) {
            $roles[] = __('Assistant HR');
        }
        if ($this->relationLoaded('members') && $this->members->contains('id', $userId)) {
            $roles[] = __('Team Member');
        }

        return $roles !== [] ? implode(', ', $roles) : '-';
    }
}
