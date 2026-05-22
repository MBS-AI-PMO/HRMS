<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeRegistrationSetting extends Model
{
    protected $fillable = [
        'company_id',
        'is_enabled',
        'page_title',
        'intro_text',
        'success_message',
        'allow_department_selection',
        'allow_designation_selection',
        'allow_shift_selection',
        'default_department_id',
        'default_designation_id',
        'default_office_shift_id',
        'default_role_users_id',
        'default_attendance_type',
        'auto_approve',
        'form_fields',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'allow_department_selection' => 'boolean',
        'allow_designation_selection' => 'boolean',
        'allow_shift_selection' => 'boolean',
        'auto_approve' => 'boolean',
        'form_fields' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(company::class, 'company_id');
    }

    public static function defaultFormFields(): array
    {
        return [
            'first_name' => ['enabled' => true, 'required' => true],
            'last_name' => ['enabled' => true, 'required' => true],
            'email' => ['enabled' => true, 'required' => true],
            'contact_no' => ['enabled' => true, 'required' => true],
            'cnic' => ['enabled' => true, 'required' => true],
            'date_of_birth' => ['enabled' => true, 'required' => true],
            'gender' => ['enabled' => true, 'required' => false],
            'username' => ['enabled' => true, 'required' => true],
            'joining_date' => ['enabled' => true, 'required' => true],
            'profile_photo' => ['enabled' => true, 'required' => false],
        ];
    }

    public function resolvedFormFields(): array
    {
        $fields = array_replace_recursive(static::defaultFormFields(), $this->form_fields ?? []);
        unset($fields['staff_id'], $fields['password']);

        return $fields;
    }

    public static function registrationUrl(int $companyId): string
    {
        $company = company::find($companyId);
        $slug = $company ? $company->ensureRegistrationSlug() : (string) $companyId;

        return route('employee.register.company', $slug);
    }

    public static function forCompany(int $companyId): self
    {
        return static::firstOrCreate(
            ['company_id' => $companyId],
            [
                'is_enabled' => false,
                'allow_department_selection' => true,
                'allow_designation_selection' => true,
                'allow_shift_selection' => false,
                'default_role_users_id' => 3,
                'default_attendance_type' => 'location_based',
            ]
        );
    }
}
