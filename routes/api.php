<?php

use App\Http\Controllers\Api\AllowanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::get('/ping', function () {
        return response()->json(['message' => 'pong'], 200);
    });
});

Route::group(['prefix' => 'auth', 'middleware' => 'throttle:api'], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('divisions', DivisionController::class);
    Route::prefix('divisions')->group(function () {
        Route::post('/restore/{uuid}', [DivisionController::class, 'restore']);
        Route::delete('/force-delete/{uuid}', [DivisionController::class, 'forceDelete']);
    });
    Route::apiResource('allowances', AllowanceController::class);
    Route::prefix('allowances')->group(function () {
        Route::post('/restore/{uuid}', [AllowanceController::class, 'restore']);
        Route::delete('/force-delete/{uuid}', [AllowanceController::class, 'forceDelete']);
    });
});
