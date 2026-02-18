<?php

namespace App\Services\Attendance\Validators;

use App\Exceptions\Attendance\GeoLocationException;
use App\Models\Setting;

class GeoFenceValidator
{
    public function validate(float $lat, float $lon): void
    {
        $geoSettings = $this->getGeoSetting();

        if (! $geoSettings) {
            return; // Geo-fencing disabled or not configured
        }

        // Calculate distance
        $distance = $this->distanceInMeters(
            $geoSettings['office_latitude'],
            $geoSettings['office_longitude'],
            $lat,
            $lon
        );

        if ($distance > $geoSettings['radius_meters']) {
            throw GeoLocationException::outsideRadius(
                $distance, 
                $geoSettings['radius_meters'], 
                $lat, 
                $lon
            );
        }
    }

    protected function getGeoSetting(): ?array
    {
        $setting = Setting::where('key', 'geo_fencing')->first();

        return $setting?->values;
    }

    protected function distanceInMeters($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // Earth radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
