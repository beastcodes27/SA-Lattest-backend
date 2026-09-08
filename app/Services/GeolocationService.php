<?php

namespace App\Services;

class GeolocationService
{
    private const EARTH_RADIUS_METERS = 6371000;

    public function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($dLng / 2) ** 2;

        return 2 * self::EARTH_RADIUS_METERS * asin(sqrt($a));
    }
}
