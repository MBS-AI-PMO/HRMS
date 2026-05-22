<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class company extends Model
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
		return $this->hasOne('App\Models\location','id','location_id');
	}

	public function companyType(){
		return $this->belongsTo(CompanyType::class,'company_type_id');
	}
}
