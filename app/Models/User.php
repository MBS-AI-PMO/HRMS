<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationRecipientResolver;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;
use RuntimeException;
use Spatie\Permission\Traits\HasRoles;
use Throwable;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'role_users_id',
        'contact_no',
        'profile_photo',
        'profile_bg',
        'is_active',
        'last_login_ip',
        'last_login_date',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function RoleUser()
    {
        return $this->hasone('Spatie\Permission\Models\Role', 'id', 'role_users_id');
    }

    /**
     * Laravel mail notifications read email from here — resolve users + employees tables.
     */
    public function routeNotificationForMail($notification = null): ?string
    {
        return NotificationRecipientResolver::resolveUserEmailAddress((int) $this->id);
    }

    public function getLastLoginDateAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format(env('Date_Format').'--H:i');
        }

        return null;
    }

    /**
     * Run before DB::beginTransaction() — cleanup only (no ALTER TABLE).
     */
    public static function prepareRegistrationStorage(array $attributes): void
    {
        static::removeInvalidUserRows($attributes);
    }

    /**
     * Create user with a valid id (no migration required).
     */
    public static function createAccount(array $attributes): self
    {
        unset($attributes['id']);

        $row = static::buildInsertRow($attributes);
        $userId = static::insertUserRow($row);

        if ($userId < 1) {
            throw new RuntimeException(__('Could not create user account. Please contact administrator.'));
        }

        return static::query()->findOrFail($userId);
    }

    protected static function buildInsertRow(array $attributes): array
    {
        $now = now();
        $allowed = array_flip((new static)->getFillable());
        $row = array_intersect_key($attributes, $allowed);
        $row['created_at'] = $now;
        $row['updated_at'] = $now;

        if (isset($row['email'])) {
            $row['email'] = strtolower(trim((string) $row['email']));
        }
        if (isset($row['username'])) {
            $row['username'] = strtolower(trim((string) $row['username']));
        }

        return $row;
    }

    protected static function insertUserRow(array $row): int
    {
        try {
            $userId = (int) DB::table('users')->insertGetId($row);
            if ($userId > 0) {
                return $userId;
            }
        } catch (Throwable $e) {
            Log::debug('users insertGetId failed, using manual id', ['message' => $e->getMessage()]);
        }

        return static::insertUserRowWithManualId($row);
    }

    /**
     * Insert user with explicit id when AUTO_INCREMENT / PRIMARY KEY is broken.
     */
    protected static function insertUserRowWithManualId(array $row): int
    {
        $nextId = static::nextAvailableUserId();
        $row['id'] = $nextId;
        DB::table('users')->insert($row);

        return $nextId;
    }

    protected static function nextAvailableUserId(): int
    {
        $nextId = max(1, (int) DB::table('users')->max('id') + 1);

        while (DB::table('users')->where('id', $nextId)->exists()) {
            $nextId++;
        }

        return $nextId;
    }

    protected static function removeInvalidUserRows(array $attributes): void
    {
        DB::table('users')->where('id', 0)->delete();

        if (! empty($attributes['email'])) {
            $email = strtolower(trim((string) $attributes['email']));
            DB::table('users')->where('id', 0)->where('email', $email)->delete();

            if (Schema::hasTable('employees')) {
                DB::table('employees')->where('id', 0)->where('email', $email)->delete();
            }
        }

        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')
                ->where('model_id', 0)
                ->where('model_type', 'App\Models\User')
                ->delete();
        }
    }

}
