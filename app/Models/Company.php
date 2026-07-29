<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
	protected $fillable = [
		'company_name', 'registration_slug', 'company_type_id','trading_name', 'registration_no','contact_no','email','website','tax_no','location_id','company_logo',
	];

	public static function makeUniqueRegistrationSlug(string $companyName, ?int $ignoreId = null): string
	{
		$base = Str::slug($companyName);
		if ($base === '') {
			$base = 'company';
		}

		$slug = $base;
		$counter = 1;

		while (static::query()
			->where('registration_slug', $slug)
			->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
			->exists()) {
			$slug = $base.'-'.$counter;
			$counter++;
		}

		return $slug;
	}

	public function ensureRegistrationSlug(): string
	{
		if (! empty($this->registration_slug)) {
			return $this->registration_slug;
		}

		$slug = static::makeUniqueRegistrationSlug((string) $this->company_name, (int) $this->id);
		$this->forceFill(['registration_slug' => $slug])->saveQuietly();

		return $slug;
	}

	public static function findByRegistrationSlug(string $slug): ?self
	{
		return static::where('registration_slug', $slug)->first();
	}

	public function companyHolidays(){
		return $this->hasMany(Holiday::class)
			->select('id','start_date','end_date','is_publish','company_id')
			->where('is_publish','=',1);
	}

	public function Location(){
		return $this->hasOne('App\Models\Location','id','location_id');
	}

	public function companyType(){
		return $this->belongsTo(CompanyType::class,'company_type_id');
	}

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'company_location', 'company_id', 'location_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'company_id');
    }

    /**
     * Human-readable reason if this company cannot be deleted, otherwise null.
     */
    public function deletionBlockReason(): ?string
    {
        $dependencies = $this->dependencySummary();

        if ($dependencies === []) {
            return null;
        }

        return __('Company have dependencies.')
            .' '
            .__('Related records: :items.', ['items' => implode(', ', $dependencies)]);
    }

    /**
     * @return array<int, string>
     */
    public function dependencySummary(): array
    {
        $checks = [
            [Employee::class, 'company_id', __('Employees')],
            [Department::class, 'company_id', __('Departments')],
            [Designation::class, 'company_id', __('Designations')],
            [Project::class, 'company_id', __('Projects')],
            [OfficeShift::class, 'company_id', __('Office Shifts')],
            [Team::class, 'company_id', __('Teams')],
            [Task::class, 'company_id', __('Tasks')],
            [Leave::class, 'company_id', __('Leaves')],
            [Client::class, 'parent_company_id', __('Clients')],
        ];

        $found = [];

        foreach ($checks as [$model, $column, $label]) {
            if ($model::query()->withoutGlobalScopes()->where($column, $this->id)->exists()) {
                $found[] = $label;
            }
        }

        if (\Illuminate\Support\Facades\DB::table('company_location')->where('company_id', $this->id)->exists()) {
            $found[] = __('Locations');
        }

        return $found;
    }
}
