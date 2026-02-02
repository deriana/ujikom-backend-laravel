<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseName = config('app.name');
        $currentYear = date('Y');

        Setting::updateOrCreate(
            ['key' => 'general'],
            ['values' => [
                'logo' => '/logo/logo-long.webp',
                'favicon' => '/logo/favicon.jpeg',
                'site_name' => $baseName,
                'footer' => "Copyright {$currentYear} © {$baseName}",
            ]]
        );

        Setting::updateOrCreate(
            ['key' => 'attendance'],
            ['values' => [
                'late_tolerance_minutes' => 10,
                'work_start_time' => '09:00',
                'work_end_time' => '17:00',
            ]]
        );

        Setting::updateOrCreate(
            ['key' => 'geo_fencing'],
            ['values' => [
                'office_latitude' => -6.200000,
                'office_longitude' => 106.816666,
                'radius_meters' => 100,
            ]]
        );
    }
}
