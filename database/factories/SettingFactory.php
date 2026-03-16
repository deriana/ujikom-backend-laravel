<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'key' => 'general',
            'values' => [
                'site_name' => 'Hideri HRIS',
                'footer' => '© 2026 Hideri Team',
            ],
        ];
    }

    // State khusus untuk Attendance
    public function attendance()
    {
        return $this->state(fn () => [
            'key' => 'attendance',
            'values' => [
                'late_tolerance_minutes' => 15,
                'work_start_time' => '08:00',
                'work_end_time' => '17:00',
            ],
        ]);
    }

    // State khusus untuk Geo Fencing
    public function geoFencing()
    {
        return $this->state(fn () => [
            'key' => 'geo_fencing',
            'values' => [
                'office_latitude' => -6.200000,
                'office_longitude' => 106.816666,
                'radius_meters' => 100,
            ],
        ]);
    }
}
