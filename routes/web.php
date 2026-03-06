<?php

use App\Http\Controllers\Auth\VerificationController;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Fruitcake\LaravelDebugbar\Facades\Debugbar;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function() {
    return "Hollo";
});

Route::get('/verify-email', [VerificationController::class, 'verify'])->name('verification.verify');
Route::get('/verify-pending', [VerificationController::class, 'pending'])->name('verification.pending');


/*
|--------------------------------------------------------------------------
| Debug Route
|--------------------------------------------------------------------------
*/

Route::get('/debug', function () {

    $users = User::with('employee', 'roles')->get();

    Debugbar::info($users);

    return view('welcome');
});
