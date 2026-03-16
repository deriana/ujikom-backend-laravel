<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AppServiceProvider
 *
 * Provider layanan utama aplikasi untuk mengatur konfigurasi boot dan registrasi service.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Mendaftarkan layanan aplikasi ke dalam service container.
     */
    public function register(): void
    {
        //
    }

    /**
     * Melakukan bootstrap pada layanan aplikasi.
     *
     * Mengatur konfigurasi nama aplikasi dari database, pembatasan laju (rate limiting) API,
     * dan otorisasi super-admin.
     */
    public function boot(): void
    {
        if (! app()->runningInConsole() && Schema::hasTable('settings')) {
            $generalSetting = Setting::where('key', 'general')->first();

            if ($generalSetting && isset($generalSetting->values['site_name'])) {
                Config::set('app.name', $generalSetting->values['site_name']);
            }
        }

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(1000)->by($request->user()?->id ?: $request->ip())->response(function () {
                return response()->json([
                    'message' => 'Too many requests. Please slow down your requests.',
                ], 429);
            });
        });

        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });
    }
}
