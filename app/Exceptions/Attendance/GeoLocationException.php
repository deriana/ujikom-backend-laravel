<?php

namespace App\Exceptions\Attendance;

class GeoLocationException extends AttendanceException
{
    public static function outsideRadius(float $distance, float $radius, float $lat, float $lon): self
    {
        return new self('Anda berada di luar area absensi.', [
            'reason' => 'outside_geofence',
            'distance_meters' => $distance,
            'allowed_radius' => $radius,
            'user_latitude' => $lat,
            'user_longitude' => $lon,
        ]);
    }

    public static function missingCoordinates(): self
    {
        return new self('Lokasi GPS wajib diaktifkan.', ['reason' => 'gps_missing']);
    }

    public static function invalidCoordinates(): self
    {
        return new self('Format koordinat tidak valid.', ['reason' => 'gps_invalid_format']);
    }
}
