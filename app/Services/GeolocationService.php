<?php

namespace App\Services;

class GeolocationService
{
    private const EARTH_RADIUS_METERS = 6_371_000;

    /**
     * Calculate distance in meters between two coordinates using the Haversine formula.
     */
    public function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    /**
     * Check whether the given coordinates are within $radiusMeters of the venue centre.
     */
    public function isWithinRange(float $venueLat, float $venueLng, float $guestLat, float $guestLng, float $radiusMeters = 200): bool
    {
        return $this->distanceInMeters($venueLat, $venueLng, $guestLat, $guestLng) <= $radiusMeters;
    }
}
