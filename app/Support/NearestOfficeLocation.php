<?php

namespace App\Support;

use App\Models\location;
use App\Scopes\AuthCompanyLocationScope;
use App\Support\CompanyScope;
use Illuminate\Support\Collection;

class NearestOfficeLocation
{
    /**
     * @return array{name: string, distance_km: float, latitude: float, longitude: float}|null
     */
    public static function find(?float $lat, ?float $lng, ?int $companyId, Collection $candidates): ?array
    {
        if ($lat === null || $lng === null || $candidates->isEmpty()) {
            return null;
        }

        $nearest = null;
        $minDistance = null;

        foreach ($candidates as $office) {
            $officeLat = $office->latitude !== null ? (float) $office->latitude : null;
            $officeLng = $office->longitude !== null ? (float) $office->longitude : null;

            if ($officeLat === null || $officeLng === null) {
                continue;
            }

            $distance = static::distanceInKilometers($lat, $lng, $officeLat, $officeLng);

            if ($distance === null) {
                continue;
            }

            if ($minDistance === null || $distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $office;
            }
        }

        if ($nearest === null || $minDistance === null) {
            return null;
        }

        return [
            'name' => (string) $nearest->location_name,
            'distance_km' => $minDistance,
            'latitude' => (float) $nearest->latitude,
            'longitude' => (float) $nearest->longitude,
        ];
    }

    public static function candidates(?int $companyId = null): Collection
    {
        $query = location::withoutGlobalScope(AuthCompanyLocationScope::class)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', '')
            ->where('longitude', '!=', '')
            ->select('id', 'location_name', 'latitude', 'longitude');

        $scopedCompanyId = $companyId;

        if ($scopedCompanyId === null && CompanyScope::applies()) {
            $scopedCompanyId = CompanyScope::companyId();
        }

        if ($scopedCompanyId) {
            $query->whereHas('companies', function ($builder) use ($scopedCompanyId) {
                $builder->where('companies.id', $scopedCompanyId);
            });
        }

        return $query->get();
    }

    protected static function distanceInKilometers(float $lat1, float $lng1, float $lat2, float $lng2): ?float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(($earthRadius * $c) / 1000, 2);
    }
}
