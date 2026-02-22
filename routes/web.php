<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\VerificationController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/verify-email', [VerificationController::class, 'verify'])->name('verification.verify');
Route::get('/verify-pending', [VerificationController::class, 'pending'])->name('verification.pending');
