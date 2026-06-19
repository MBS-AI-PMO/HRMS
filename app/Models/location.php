<?php

namespace App\Models;

use App\Scopes\AuthCompanyLocationScope;
use App\Scopes\AuthCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public function locationHeads()
    {
        return $this->belongsToMany(Employee::class, 'location_heads', 'location_id', 'employee_id')
            ->withTimestamps();
    }

    public function companies()
    {
        return $this->belongsToMany(company::class, 'company_location', 'location_id', 'company_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'location_id');
    }

    public function locationHeadsLabel(): string
    {
        $this->loadMissing('locationHeads:id,first_name,last_name');

        return $this->locationHeads->pluck('full_name')->filter()->implode(', ') ?: '-';
    }

    public static function userHeadsLocation(int $userId, int $locationId): bool
    {
        return in_array($locationId, static::locationIdsHeadedByUser($userId), true);
    }

    public static function userCanManageHeadedLocation(int $userId, int $locationId): bool
    {
        return static::userHeadsLocation($userId, $locationId);
    }

    public static function userIsLocationHead(int $userId): bool
    {
        if (DB::table('location_heads')->where('employee_id', $userId)->exists()) {
            return true;
        }

        return static::withoutGlobalScope(AuthCompanyLocationScope::class)
            ->where('location_head', $userId)
            ->exists();
    }

    public static function locationIdsHeadedByUser(int $userId): array
    {
        $fromPivot = DB::table('location_heads')
            ->where('employee_id', $userId)
            ->pluck('location_id');

        $fromLegacy = static::withoutGlobalScope(AuthCompanyLocationScope::class)
            ->where('location_head', $userId)
            ->pluck('id');

        return $fromPivot
            ->merge($fromLegacy)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function locationNamesHeadedByUser(int $userId): array
    {
        $locationIds = static::locationIdsHeadedByUser($userId);

        if ($locationIds === []) {
            return [];
        }

        return static::withoutGlobalScope(AuthCompanyLocationScope::class)
            ->whereIn('id', $locationIds)
            ->orderBy('location_name')
            ->pluck('location_name')
            ->filter()
            ->values()
            ->all();
    }

    public static function deletionBlockReasonForLocationHead(int $userId): ?string
    {
        $locationNames = static::locationNamesHeadedByUser($userId);

        if ($locationNames === []) {
            return null;
        }

        return __('This employee is assigned as location head for: :locations. Unassign them from the location first, then delete.', [
            'locations' => implode(', ', $locationNames),
        ]);
    }

    public static function employeeIdsAtLocationsHeadedByUser(int $userId): array
    {
        $locationIds = static::locationIdsHeadedByUser($userId);

        if ($locationIds === []) {
            return [];
        }

        return Employee::withoutGlobalScope(AuthCompanyScope::class)
            ->whereIn('location_id', $locationIds)
            ->where('is_active', 1)
            ->whereNull('exit_date')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public static function userCanAccessLocationEmployeeList(int $userId): bool
    {
        return static::userIsLocationHead($userId);
    }

    public static function userCanManageLocationLeaveRequests(int $userId): bool
    {
        return static::userIsLocationHead($userId);
    }

    public function scopeHeadedByUser($query, int $userId)
    {
        return $query->where(function ($builder) use ($userId) {
            $builder->where('location_head', $userId)
                ->orWhereHas('locationHeads', function ($heads) use ($userId) {
                    $heads->where('employees.id', $userId);
                });
        });
    }
}
