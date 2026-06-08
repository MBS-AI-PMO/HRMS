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

    public function departmentHeads()
    {
        return $this->belongsToMany(Employee::class, 'team_department_heads', 'team_id', 'employee_id')
            ->withTimestamps();
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

    public function leaderEmployeeIds(): array
    {
        $this->loadMissing('departmentHeads:id');

        return collect($this->departmentHeads->pluck('id'))
            ->push($this->project_manager_id)
            ->push($this->assistant_hr_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function departmentHeadsLabel(): string
    {
        $this->loadMissing('departmentHeads:id,first_name,last_name');

        return $this->departmentHeads->pluck('full_name')->filter()->implode(', ') ?: '-';
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($builder) use ($userId) {
            $builder->where('project_manager_id', $userId)
                ->orWhere('assistant_hr_id', $userId)
                ->orWhereHas('departmentHeads', function ($heads) use ($userId) {
                    $heads->where('employees.id', $userId);
                })
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
            $builder->where('project_manager_id', $userId)
                ->orWhere('assistant_hr_id', $userId)
                ->orWhereHas('departmentHeads', function ($heads) use ($userId) {
                    $heads->where('employees.id', $userId);
                });
        });
    }

    public static function userCanLeadAnyTeam(int $userId): bool
    {
        return static::query()->ledByUser($userId)->exists();
    }

    /**
     * Team leaders (DH / PM / Assistant HR) may open the employee list for their team members
     * without any separate Spatie permission.
     */
    public static function userCanAccessEmployeeList(int $userId): bool
    {
        return static::userCanLeadAnyTeam($userId);
    }

    public static function memberEmployeeIdsLedByUser(int $userId): array
    {
        return static::query()
            ->ledByUser($userId)
            ->with('members:id')
            ->get()
            ->flatMap(fn ($team) => $team->members->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function userIsLeader(int $userId): bool
    {
        return in_array($userId, $this->leaderEmployeeIds(), true);
    }

    public function roleLabelForUser(int $userId): string
    {
        $roles = [];

        $this->loadMissing('departmentHeads:id', 'members:id');

        if ($this->departmentHeads->contains('id', $userId)) {
            $roles[] = __('Department Head');
        }
        if ((int) $this->project_manager_id === $userId) {
            $roles[] = __('Project Manager');
        }
        if ((int) $this->assistant_hr_id === $userId) {
            $roles[] = __('Assistant HR');
        }
        if ($this->members->contains('id', $userId)) {
            $roles[] = __('Team Member');
        }

        return $roles !== [] ? implode(', ', $roles) : '-';
    }
}
