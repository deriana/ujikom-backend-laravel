<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->User()?->id ?: $request->ip())->response(function () {
            return response()->json([
                'message' => 'Too many requests. Please slow down your requests.',
            ], 429);
        });
    });
    }
}
