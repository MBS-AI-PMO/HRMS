<?php

namespace App\Support;

use Illuminate\Http\Request;

class AttendanceLocationCapture
{
    public static function clockInFields(Request $request): array
    {
        $fields = [];

        if (is_numeric($request->input('latitude'))) {
            $fields['clock_in_latitude'] = (float) $request->input('latitude');
        }

        if (is_numeric($request->input('longitude'))) {
            $fields['clock_in_longitude'] = (float) $request->input('longitude');
        }

        return $fields;
    }

    public static function clockOutFields(Request $request): array
    {
        $fields = [];

        if (is_numeric($request->input('latitude'))) {
            $fields['clock_out_latitude'] = (float) $request->input('latitude');
        }

        if (is_numeric($request->input('longitude'))) {
            $fields['clock_out_longitude'] = (float) $request->input('longitude');
        }

        return $fields;
    }

    public static function metaFromRequest(Request $request): array
    {
        $meta = [];

        if (is_numeric($request->input('latitude'))) {
            $meta['latitude'] = (float) $request->input('latitude');
        }

        if (is_numeric($request->input('longitude'))) {
            $meta['longitude'] = (float) $request->input('longitude');
        }

        return $meta;
    }
}
