<?php

namespace App\Support;

use Illuminate\Http\Request;

class AttendanceLocationCapture
{
    protected static function normalizeCoordinate($value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 7);
    }

    public static function clockInFields(Request $request): array
    {
        $fields = [];

        $latitude = static::normalizeCoordinate($request->input('latitude'));
        $longitude = static::normalizeCoordinate($request->input('longitude'));

        if ($latitude !== null) {
            $fields['clock_in_latitude'] = $latitude;
        }

        if ($longitude !== null) {
            $fields['clock_in_longitude'] = $longitude;
        }

        return $fields;
    }

    public static function clockOutFields(Request $request): array
    {
        $fields = [];

        $latitude = static::normalizeCoordinate($request->input('latitude'));
        $longitude = static::normalizeCoordinate($request->input('longitude'));

        if ($latitude !== null) {
            $fields['clock_out_latitude'] = $latitude;
        }

        if ($longitude !== null) {
            $fields['clock_out_longitude'] = $longitude;
        }

        return $fields;
    }

    public static function metaFromRequest(Request $request): array
    {
        $meta = [];

        $latitude = static::normalizeCoordinate($request->input('latitude'));
        $longitude = static::normalizeCoordinate($request->input('longitude'));

        if ($latitude !== null) {
            $meta['latitude'] = $latitude;
        }

        if ($longitude !== null) {
            $meta['longitude'] = $longitude;
        }

        if (is_numeric($request->input('location_accuracy'))) {
            $meta['gps_accuracy_m'] = round((float) $request->input('location_accuracy'), 2);
        }

        if ($request->filled('location_captured_at')) {
            $meta['gps_captured_at'] = (string) $request->input('location_captured_at');
        }

        return $meta;
    }

    public static function gpsValidationError(Request $request): ?string
    {
        if (! is_numeric($request->input('latitude')) || ! is_numeric($request->input('longitude'))) {
            return __('GPS location is required to clock in or out. Please allow location access and try again.');
        }

        return null;
    }
}
