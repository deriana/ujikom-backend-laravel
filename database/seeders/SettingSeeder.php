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
        $current_year = date('Y');
        $values = [
            'general' => [
                'logo' => "/logo/logo-long.webp",
                'logo_sm' => "/logo/logo_sm.jpeg",
                'favicon' => "/logo/favicon.jpeg",
                'site_name' => $baseName,
                'footer' => "Copyright {$current_year} © {$baseName}",
            ],
            'google_reCaptcha' => [
                'site_key' => null,
                'secret' => null,
                'status' => 1,
            ],
            'social_login' => [
                'google' => [
                    'google_client_id' => null,
                    'google_client_secret' => null,
                    'google_redirect_url' => null,
                    'google_login_status' => 1,
                ],

                'facebook' => [
                    'facebook_client_id' => null,
                    'facebook_client_secret' => null,
                    'facebook_redirect_url' => null,
                    'facebook_login_status' => 1,
                ],
            ],
        ];
        Setting::updateOrCreate(['values' => $values]);
    }
}

