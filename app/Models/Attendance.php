<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{

	protected $guarded = [];

	public $timestamps = false;


	public function employee(){
		return $this->belongsTo(Employee::class);
	}

	public function setAttendanceDateAttribute($value)
	{
		$this->attributes['attendance_date'] = Carbon::createFromFormat(config('variable.date_format', 'd-m-Y'), $value)->format('Y-m-d');
	}

	public function getAttendanceDateAttribute($value)
	{
		return Carbon::parse($value)->format(config('variable.date_format', 'd-m-Y'));
	}

	/**
	 * Display clock times in 12-hour format (e.g. 03:50 PM). DB still stores 24h H:i.
	 */
	public static function formatClockDisplay(?string $time): string
	{
		$time = trim((string) $time);
		if ($time === '' || $time === '---' || $time === '—') {
			return '---';
		}

		foreach (['H:i:s', 'H:i', 'h:i A', 'h:iA'] as $format) {
			try {
				return Carbon::createFromFormat($format, $time)->format('h:i A');
			} catch (\Throwable $e) {
				continue;
			}
		}

		return $time;
	}
}
