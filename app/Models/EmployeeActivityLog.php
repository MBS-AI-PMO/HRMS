<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeActivityLog extends Model
{
    protected $fillable = [
        'employee_id',
        'performed_by',
        'action',
        'description',
        'meta',
        'ip_address',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public static function write(
        int $employeeId,
        ?int $performedBy,
        string $action,
        string $description,
        ?array $meta = null,
        ?string $ipAddress = null
    ): self {
        return self::create([
            'employee_id' => $employeeId,
            'performed_by' => $performedBy,
            'action' => $action,
            'description' => $description,
            'meta' => $meta,
            'ip_address' => $ipAddress,
        ]);
    }
}
