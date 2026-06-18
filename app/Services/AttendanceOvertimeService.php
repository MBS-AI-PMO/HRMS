<?php

namespace App\Services;

use App\Models\Attendance;
use DateTime;

class AttendanceOvertimeService
{
    public static function isShiftEnded(?string $shiftOutTime): bool
    {
        if (empty($shiftOutTime)) {
            return false;
        }

        return self::isShiftEndedDateTime(new DateTime($shiftOutTime), new DateTime(date('H:i')));
    }

    public static function isShiftEndedDateTime(DateTime $shiftOut, DateTime $currentTime): bool
    {
        return $currentTime > $shiftOut;
    }

    public static function canStartOvertime(?string $shiftOut, ?Attendance $lastAttendance): bool
    {
        if (! self::isShiftEnded($shiftOut)) {
            return false;
        }

        if (! $lastAttendance) {
            return true;
        }

        return (int) $lastAttendance->clock_in_out === 0;
    }

    public static function isActiveOvertimeSession(?Attendance $attendance): bool
    {
        return $attendance
            && (int) $attendance->clock_in_out === 1
            && (int) ($attendance->is_overtime ?? 0) === 1;
    }

    public static function shouldClockInAsOvertime(DateTime $shiftOut, DateTime $currentTime, ?Attendance $lastAttendance): bool
    {
        if (! self::isShiftEndedDateTime($shiftOut, $currentTime)) {
            return false;
        }

        if ($lastAttendance && (int) $lastAttendance->clock_in_out === 1) {
            return false;
        }

        return true;
    }

    public static function applyOvertimeClockInDefaults(array $data, DateTime $currentTime): array
    {
        $data['clock_in'] = $currentTime->format('H:i');
        $data['is_overtime'] = 1;
        $data['time_late'] = '00:00';
        $data['total_rest'] = $data['total_rest'] ?? '00:00';
        $data['total_work'] = '00:00';
        $data['overtime'] = '00:00';
        $data['early_leaving'] = '00:00';

        return $data;
    }

    public static function buildOvertimeClockOutUpdate(Attendance $attendance, DateTime $currentTime, string $ip): array
    {
        $clockIn = new DateTime($attendance->clock_in);
        $clockOut = $currentTime->format('H:i');
        $prevWork = new DateTime($attendance->total_work ?: '00:00');
        $totalWork = clone $prevWork;
        $totalWork->add($clockIn->diff(new DateTime($clockOut)));
        $duration = $totalWork->format('H:i');

        return [
            'clock_out' => $clockOut,
            'clock_out_ip' => $ip,
            'clock_in_out' => 0,
            'total_work' => $duration,
            'overtime' => $duration,
        ];
    }

    public static function sumTodayOvertime(int $employeeId, string $dateYmd): string
    {
        $rows = Attendance::where('attendance_date', $dateYmd)
            ->where('employee_id', $employeeId)
            ->get();

        $totalMinutes = 0;

        foreach ($rows as $row) {
            if (empty($row->overtime) || $row->overtime === '00:00') {
                continue;
            }

            [$hours, $minutes] = array_pad(explode(':', $row->overtime), 2, 0);
            $totalMinutes += ((int) $hours * 60) + (int) $minutes;
        }

        return sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);
    }
}
