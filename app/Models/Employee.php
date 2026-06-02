<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Employee extends Model
{
    use Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'staff_id',
        'email',
        'contact_no',
        'cnic',
        'date_of_birth',
        'gender',
        'status_id',
        'office_shift_id',
        'salary_id',
        'location_id',
        'designation_id',
        'company_id',
        'department_id',
        'is_active',
        'role_users_id',
        'permission_role_id',
        'joining_date',
        'exit_date',
        'marital_status',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'cv',
        'skype_id',
        'fb_id',
        'twitter_id',
        'linkedIn_id',
        'blogger_id',
        'basic_salary',
        'payslip_type',
        'leave_id',
        'attendance_id',
        'performance_id',
        'award_id',
        'transfer_id',
        'resignation_id',
        'travel_id',
        'promotion_id',
        'complain_id',
        'warning_id',
        'termination_id',
        'attendance_type',
        'total_leave',
        'remaining_leave',
        'pension_type',
        'pension_amount',
    ];

    public function getFullNameAttribute()
    {
        return ucfirst($this->first_name) . ' ' . ucfirst($this->last_name);
    }

    public function getBirthDateAttribute()
    {
        return $this->date_of_birth;
    }

    public function department()
    {
        return $this->hasOne('App\Models\department', 'id', 'department_id');
    }

    public function officeShift()
    {
        return $this->hasOne('App\Models\office_shift', 'id', 'office_shift_id');
    }

    public function location()
    {
        return $this->hasOne('App\Models\location', 'id', 'location_id');
    }

    public function company()
    {
        return $this->hasOne('App\Models\company', 'id', 'company_id');
    }

    public function designation()
    {
        return $this->hasOne('App\Models\designation', 'id', 'designation_id');
    }

    public function status()
    {
        return $this->hasOne('App\Models\status', 'id', 'status_id');
    }

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'id');
    }

    public function role()
    {
        return $this->hasOne('Spatie\Permission\Models\Role', 'id', 'role_users_id');
    }

    public function salaryBasic()
    {
        return $this->hasMany(SalaryBasic::class);
    }

    public function allowances()
    {
        return $this->hasMany(SalaryAllowance::class);
    }

    public function deductions()
    {
        return $this->hasMany(SalaryDeduction::class);
    }

    public function commissions()
    {
        return $this->hasMany(SalaryCommission::class);
    }

    public function loans()
    {
        return $this->hasMany(SalaryLoan::class);
    }

    public function otherPayments()
    {
        return $this->hasMany(SalaryOtherPayment::class);
    }

    public function overtimes()
    {
        return $this->hasMany(SalaryOvertime::class);
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function payslipNew()
    {
        return $this->hasOne(Payslip::class);
    }

    public function employeeAttendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function employeeLeave()
    {
        return $this->hasMany(leave::class)
            ->select('id', 'start_date', 'end_date', 'status', 'employee_id', 'leave_type_id', 'total_days')
            ->whereStatus('approved');
    }

    public function employeeLeaveTypeDetail()
    {
        return $this->hasOne(EmployeeLeaveTypeDetail::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(EmployeeActivityLog::class);
    }

    public function setDateOfBirthAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['date_of_birth'] = null;
            return;
        }

        $this->attributes['date_of_birth'] = Carbon::createFromFormat(env('Date_Format'), trim($value))->format('Y-m-d');
    }

    public function getDateOfBirthAttribute($value)
    {
        if (empty($value)) {
            return '';
        }

        return Carbon::parse($value)->format(env('Date_Format'));
    }

    public function setJoiningDateAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['joining_date'] = null;
            return;
        }

        $this->attributes['joining_date'] = Carbon::createFromFormat(env('Date_Format'), trim($value))->format('Y-m-d');
    }

    public function getJoiningDateAttribute($value)
    {
        if (empty($value)) {
            return '';
        }

        return Carbon::parse($value)->format(env('Date_Format'));
    }

    public function setExitDateAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['exit_date'] = null;
            return;
        }

        $this->attributes['exit_date'] = Carbon::createFromFormat(env('Date_Format'), trim($value))->format('Y-m-d');
    }

    public function getExitDateAttribute($value)
    {
        if (empty($value)) {
            return '';
        }

        return Carbon::parse($value)->format(env('Date_Format'));
    }

    /**
     * Create employee row using the same primary key as the user account.
     */
    public static function createForUser(User $user, array $attributes): self
    {
        $user->refresh();
        $userId = (int) $user->getKey();

        if ($userId < 1) {
            throw new \InvalidArgumentException('Cannot create employee: user id was not generated.');
        }

        unset($attributes['id']);

        $employee = new static($attributes);
        $employee->id = $userId;

        if (empty($employee->role_users_id)) {
            $employee->role_users_id = $user->role_users_id;
        }

        $employee->save();

        return $employee;
    }

    /**
     * Generate next staff id in sequence: EMP001, EMP002, ...
     */
    public static function generateStaffId(): string
    {
        $prefix = 'EMP';
        $padLength = 3;
        $maxNumber = 0;

        static::query()
            ->where('staff_id', 'like', $prefix.'%')
            ->pluck('staff_id')
            ->each(function ($staffId) use (&$maxNumber, $prefix) {
                if (preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/i', (string) $staffId, $matches)) {
                    $maxNumber = max($maxNumber, (int) $matches[1]);
                }
            });

        do {
            $maxNumber++;
            $candidate = $prefix.str_pad((string) $maxNumber, $padLength, '0', STR_PAD_LEFT);
        } while (static::where('staff_id', $candidate)->exists());

        return $candidate;
    }

    public static function generatePassword(): string
    {
        return Str::password(12, true, true, false, false);
    }
}