<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClientRegistrationSetting extends Model
{
    protected $fillable = [
        'client_id',
        'registration_slug',
        'label',
        'project_ids',
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
        'project_ids' => 'array',
        'is_enabled' => 'boolean',
        'allow_department_selection' => 'boolean',
        'allow_designation_selection' => 'boolean',
        'allow_shift_selection' => 'boolean',
        'auto_approve' => 'boolean',
        'form_fields' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function projects()
    {
        $ids = collect($this->project_ids ?? [])->filter()->map(fn ($id) => (int) $id)->all();

        if ($ids === []) {
            return collect();
        }

        return Project::query()
            ->whereIn('id', $ids)
            ->orderBy('title')
            ->get(['id', 'title', 'client_id']);
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

    public function ensureRegistrationSlug(): string
    {
        if (! empty($this->registration_slug)) {
            return $this->registration_slug;
        }

        $client = Client::find($this->client_id);
        $name = trim((string) ($this->label ?: ($client->company_name ?? $client->first_name ?? 'client')));
        $slug = static::makeUniqueRegistrationSlug($name, $this->id ?: null);
        $this->forceFill(['registration_slug' => $slug])->saveQuietly();

        return $slug;
    }

    public static function makeUniqueRegistrationSlug(string $name, ?int $exceptId = null): string
    {
        $base = Str::slug($name) ?: 'client-registration';
        $slug = $base;
        $i = 1;

        while (static::query()
            ->where('registration_slug', $slug)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public static function findByRegistrationSlug(string $slug): ?self
    {
        return static::where('registration_slug', $slug)->first();
    }

    public function registrationUrl(): string
    {
        $slug = $this->ensureRegistrationSlug();

        return route('client.register.link', $slug);
    }

    public function resolvedProjectIds(): array
    {
        return collect($this->project_ids ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
