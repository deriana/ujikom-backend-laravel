<?php

namespace App\Exceptions\Attendance;

class GeoLocationException extends AttendanceException
{
    public static function outsideRadius(float $distance, float $radius, float $lat, float $lon): self
    {
        return new self('You are outside the attendance area.', [
            'reason' => 'outside_geofence',
            'distance_meters' => $distance,
            'allowed_radius' => $radius,
            'user_latitude' => $lat,
            'user_longitude' => $lon,
        ]);
    }

    public static function missingCoordinates(): self
    {
        return new self('GPS location must be enabled.', ['reason' => 'gps_missing']);
    }

    public static function invalidCoordinates(): self
    {
        return new self('Invalid coordinate format.', ['reason' => 'gps_invalid_format']);
    }
}
