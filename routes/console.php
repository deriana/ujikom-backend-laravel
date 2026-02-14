<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('attendance:mark-absent')
    ->dailyAt('17:10')
    ->withoutOverlapping();
Schedule::command('holidays:refresh')->yearlyOn(1, 1, '00:00');
Schedule::command('leave:reset-balances')->yearlyOn(1, 1, '00:00');
