<?php

namespace App\Support;

use Carbon\Carbon;
use DateTime;
use Throwable;

class AppDate
{
    /**
     * Normalize UI/API date strings to Y-m-d for DB storage.
     */
    public static function toYmd(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || $value === '0000-00-00') {
            return null;
        }

        // Already ISO date (optionally with time).
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T].*)?$/', $value, $m)) {
            if (checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
            }
        }

        // App format d-m-Y / d/m/Y / d.m.Y
        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})(?:\s+.*)?$/', $value, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // US-ish m/d/Y when day > 12 would already fail above; try month-first only when first part <= 12
        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})(?:\s+.*)?$/', $value, $m)) {
            $month = (int) $m[1];
            $day = (int) $m[2];
            $year = (int) $m[3];
            if ($month <= 12 && checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        $preferred = (string) config('variable.date_format', 'd-m-Y');
        $formats = array_values(array_unique(array_filter([
            $preferred,
            'd-m-Y',
            'd/m/Y',
            'd.m.Y',
            'Y-m-d',
            'Y/m/d',
            'm/d/Y',
            'm-d-Y',
        ])));

        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat('!'.$format, $value);
            $errors = DateTime::getLastErrors();
            $ok = $dt instanceof DateTime
                && ($errors === false || ((($errors['warning_count'] ?? 0) === 0) && (($errors['error_count'] ?? 0) === 0)));

            if ($ok) {
                return $dt->format('Y-m-d');
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable $e) {
            return null;
        }
    }
}
