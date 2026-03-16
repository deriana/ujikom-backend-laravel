<?php

namespace App\Exceptions\Attendance;

/**
 * Class GeoLocationException
 *
 * Exception khusus untuk menangani kesalahan terkait lokasi geografis (Geofencing) pada absensi.
 */
class GeoLocationException extends AttendanceException
{
    /**
     * Exception ketika pengguna berada di luar radius lokasi absensi yang diizinkan.
     *
     * @param float $distance Jarak pengguna dari titik pusat (meter)
     * @param float $radius Radius maksimal yang diizinkan (meter)
     * @param float $lat Latitude pengguna
     * @param float $lon Longitude pengguna
     * @return self
     */
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

    /**
     * Exception ketika koordinat GPS tidak ditemukan dalam request.
     *
     * @return self
     */
    public static function missingCoordinates(): self
    {
        return new self('GPS location must be enabled.', ['reason' => 'gps_missing']);
    }

    /**
     * Exception ketika format koordinat GPS tidak valid.
     *
     * @return self
     */
    public static function invalidCoordinates(): self
    {
        return new self('Invalid coordinate format.', ['reason' => 'gps_invalid_format']);
    }
}
