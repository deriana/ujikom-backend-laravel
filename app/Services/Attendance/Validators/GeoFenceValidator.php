<?php

namespace App\Services\Attendance\Validators;

use App\Exceptions\Attendance\GeoLocationException;
use App\Models\Setting;
// use Illuminate\Support\Facades\Log;

/**
 * Class GeoFenceValidator
 *
 * Menangani validasi lokasi geografis (geo-fencing) untuk memastikan kehadiran dilakukan di radius kantor.
 */
class GeoFenceValidator
{
    /**
     * Memvalidasi apakah koordinat yang diberikan berada dalam radius kantor yang diizinkan.
     *
     * @param float $lat Latitude pengguna.
     * @param float $lon Longitude pengguna.
     * @param bool $isRequired Menentukan apakah validasi lokasi wajib dilakukan.
     *
     * @throws GeoLocationException Jika pengguna berada di luar radius yang diizinkan.
     */
    public function validate(float $lat, float $lon, bool $isRequired = true): void
    {
        // Log::info('Geo-fencing validation started', ['latitude' => $lat, 'longitude' => $lon, 'is_required' => $isRequired]);

        if (! $isRequired) {
            return;
        }

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

    /**
     * Mengambil konfigurasi geo-fencing dari pengaturan sistem.
     *
     * @return array|null Data pengaturan geo-fencing atau null jika tidak ditemukan.
     */
    protected function getGeoSetting(): ?array
    {
        $setting = Setting::where('key', 'geo_fencing')->first();

        return $setting?->values;
    }

    /**
     * Menghitung jarak antara dua titik koordinat menggunakan rumus Haversine.
     *
     * @param float $lat1 Latitude titik pertama.
     * @param float $lon1 Longitude titik pertama.
     * @param float $lat2 Latitude titik kedua.
     * @param float $lon2 Longitude titik kedua.
     * @return float Jarak dalam satuan meter.
     */
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
