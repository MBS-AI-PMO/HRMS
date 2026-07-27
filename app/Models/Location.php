<?php

namespace App\Models;

use App\Scopes\AuthCompanyLocationScope;
use App\Scopes\AuthCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class location extends Model
{
	protected $fillable = [
		'location_name', 'client_id', 'location_head', 'address1','address2','city','state','country','zip',
        'latitude', 'longitude', 'max_radius',
	];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

	public function country(){
		return $this->hasOne('App\Models\Country','id','country');
	}

	public function LocationHead(){
		return $this->hasOne('App\Models\Employee','id','location_head');
	}

    public function locationHeads()
    {
        return $this->belongsToMany(Employee::class, 'location_heads', 'location_id', 'employee_id')
            ->withoutGlobalScope(AuthCompanyScope::class)
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
        $this->loadMissing([
            'locationHeads:id,first_name,last_name',
            'LocationHead:id,first_name,last_name',
        ]);

        $names = $this->locationHeads->pluck('full_name')->filter();

        if ($names->isEmpty() && $this->LocationHead) {
            $names = collect([$this->LocationHead->full_name]);
        }

        return $names->implode(', ') ?: '-';
    }

    /**
     * User id plus any employee record ids linked to the same account (email / phone).
     *
     * @return array<int>
     */
    public static function resolveIdentityIds(int $userId): array
    {
        $ids = [(int) $userId];

        $user = User::query()->find($userId);

        if (! $user) {
            return $ids;
        }

        $email = strtolower(trim((string) $user->email));

        if ($email !== '') {
            $ids = array_merge(
                $ids,
                Employee::withoutGlobalScope(AuthCompanyScope::class)
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all()
            );
        }

        $contact = trim((string) $user->contact_no);

        if ($contact !== '') {
            $ids = array_merge(
                $ids,
                Employee::withoutGlobalScope(AuthCompanyScope::class)
                    ->where('contact_no', $contact)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all()
            );
        }

        return array_values(array_unique(array_filter($ids)));
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
        return static::locationIdsHeadedByUser($userId) !== [];
    }

    public static function locationIdsHeadedByUser(int $userId): array
    {
        $identityIds = static::resolveIdentityIds($userId);

        $fromPivot = DB::table('location_heads')
            ->whereIn('employee_id', $identityIds)
            ->pluck('location_id');

        $user = User::query()->find($userId);
        $email = strtolower(trim((string) ($user->email ?? '')));

        if ($email !== '') {
            $fromPivot = $fromPivot->merge(
                DB::table('location_heads')
                    ->join('employees', 'employees.id', '=', 'location_heads.employee_id')
                    ->whereRaw('LOWER(TRIM(employees.email)) = ?', [$email])
                    ->pluck('location_heads.location_id')
            );
        }

        $fromLegacy = static::withoutGlobalScope(AuthCompanyLocationScope::class)
            ->whereIn('location_head', $identityIds)
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

    public static function locationIdsFromTeamMembers(int $userId): array
    {
        if (! Team::userCanLeadAnyTeam($userId)) {
            return [];
        }

        $locationIds = collect();

        $selfLocationId = (int) (Employee::withoutGlobalScope(AuthCompanyScope::class)
            ->where('id', $userId)
            ->value('location_id') ?? 0);

        if ($selfLocationId > 0) {
            $locationIds->push($selfLocationId);
        }

        $memberIds = Team::memberEmployeeIdsLedByUser($userId);

        if ($memberIds !== []) {
            $locationIds = $locationIds->merge(
                Employee::withoutGlobalScope(AuthCompanyScope::class)
                    ->whereIn('id', $memberIds)
                    ->whereNotNull('location_id')
                    ->where('location_id', '>', 0)
                    ->pluck('location_id')
                    ->map(fn ($id) => (int) $id)
            );
        }

        return $locationIds
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Locations a user may open on "My Centers / Locations" (assigned head + team members' centers).
     *
     * @return array<int>
     */
    public static function locationIdsForMyLocationsPage(int $userId): array
    {
        return collect(static::locationIdsHeadedByUser($userId))
            ->merge(static::locationIdsFromTeamMembers($userId))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public static function userCanAccessMyLocationsPage(int $userId): bool
    {
        return static::locationIdsForMyLocationsPage($userId) !== [];
    }

    public static function userCanManageLocationForMyPage(int $userId, int $locationId): bool
    {
        return in_array($locationId, static::locationIdsForMyLocationsPage($userId), true);
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
        $identityIds = static::resolveIdentityIds($userId);

        return $query->where(function ($builder) use ($identityIds, $userId) {
            $builder->whereIn('location_head', $identityIds)
                ->orWhereHas('locationHeads', function ($heads) use ($identityIds) {
                    $heads->whereIn('employees.id', $identityIds);
                });

            $email = strtolower(trim((string) (User::query()->find($userId)->email ?? '')));

            if ($email !== '') {
                $builder->orWhereHas('locationHeads', function ($heads) use ($email) {
                    $heads->whereRaw('LOWER(TRIM(employees.email)) = ?', [$email]);
                });
            }
        });
    }
}
