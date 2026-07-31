<?php

namespace App\Support;

use Carbon\Carbon;
use Throwable;

class EmploymentPeriod
{
    /**
     * Include anyone employed for at least one day in [periodStart, periodEnd].
     * Leaving month is included; months after exit_date are excluded.
     */
    public static function applyToQuery($query, string $periodStartDate, ?string $periodEndDate = null): void
    {
        $periodEndDate = $periodEndDate ?: $periodStartDate;

        $query->where(function ($q) use ($periodStartDate) {
            $q->where('is_active', 1)
                ->orWhere(function ($left) use ($periodStartDate) {
                    $left->whereNotNull('exit_date')
                        ->where('exit_date', '!=', '')
                        ->where('exit_date', '!=', '0000-00-00')
                        ->where('exit_date', '>=', $periodStartDate);
                });
        });

        $query->where(function ($q) use ($periodEndDate) {
            $q->whereNull('joining_date')
                ->orWhere('joining_date', '')
                ->orWhere('joining_date', '0000-00-00')
                ->orWhere('joining_date', '<=', $periodEndDate);
        });

        $query->where(function ($q) use ($periodStartDate) {
            $q->whereNull('exit_date')
                ->orWhere('exit_date', '')
                ->orWhere('exit_date', '0000-00-00')
                ->orWhere('exit_date', '>=', $periodStartDate);
        });
    }

    /**
     * Resolve first day of the month for attendance/employee dropdown filters.
     * Accepts Y-m-d, d-m-Y, "July 2026", "07 2026", "F Y", etc.
     */
    public static function monthStartFromInput(?string $value): string
    {
        $fallback = now()->startOfMonth()->format('Y-m-d');

        if ($value === null) {
            return $fallback;
        }

        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        if (preg_match('/^(\d{1,2})\s+(\d{4})$/', $value, $matches)) {
            return Carbon::createFromDate((int) $matches[2], (int) $matches[1], 1)
                ->startOfMonth()
                ->format('Y-m-d');
        }

        $ymd = AppDate::toYmd($value);
        if ($ymd) {
            return Carbon::parse($ymd)->startOfMonth()->format('Y-m-d');
        }

        try {
            if (preg_match('/^[A-Za-z]+\s+\d{4}$/', $value) || preg_match('/^\d{1,2}\s+[A-Za-z]+\s+\d{4}$/', $value)) {
                return Carbon::parse('first day of '.$value)->startOfMonth()->format('Y-m-d');
            }

            return Carbon::parse($value)->startOfMonth()->format('Y-m-d');
        } catch (Throwable $e) {
            return $fallback;
        }
    }

    public static function monthEndFromStart(string $periodStartDate): string
    {
        return Carbon::parse($periodStartDate)->endOfMonth()->format('Y-m-d');
    }
}
