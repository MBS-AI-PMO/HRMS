<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
	protected $fillable = [
		'id',
        'username',
        'company_name',
        'parent_company_id',
        'first_name',
        'last_name',
        'password',
        'contact_no',
        'email',
        'gender',
        'website',
        'address1',
        'address2',
		'city',
        'state',
        'country',
        'zip',
        'profile',
        'is_active'
	];

	public function invoices()
	{
		return $this->hasMany(Invoice::class);
	}

	public function projects()
	{
		return $this->hasMany(Project::class);
	}

	public function user(){
		return $this->hasOne('App\Models\User','id','id');
	}

	public function parentCompany()
	{
		return $this->belongsTo(Company::class, 'parent_company_id');
	}

	public function employees()
	{
		return $this->hasMany(Employee::class, 'client_id');
	}

	/**
	 * Human-readable reason if this client cannot be deleted, otherwise null.
	 */
	public function deletionBlockReason(): ?string
	{
		$dependencies = $this->dependencySummary();

		if ($dependencies === []) {
			return null;
		}

		return __('Client have dependencies.')
			.' '
			.__('Related records: :items.', ['items' => implode(', ', $dependencies)]);
	}

	/**
	 * @return array<int, string>
	 */
	public function dependencySummary(): array
	{
		$checks = [
			[Employee::class, 'client_id', __('Employees')],
			[Project::class, 'client_id', __('Projects')],
			[Invoice::class, 'client_id', __('Invoices')],
			[Location::class, 'client_id', __('Locations')],
			[OfficeShift::class, 'client_id', __('Office Shifts')],
		];

		$found = [];

		foreach ($checks as [$model, $column, $label]) {
			if ($model::query()->withoutGlobalScopes()->where($column, $this->id)->exists()) {
				$found[] = $label;
			}
		}

		return $found;
	}
}
