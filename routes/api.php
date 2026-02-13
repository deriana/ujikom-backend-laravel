<?php

use App\Http\Controllers\Api\AllowanceController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AttendanceDetailController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\EmployeeShiftController;
use App\Http\Controllers\Api\EmployeeWorkScheduleController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\PositionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ShiftTemplateController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkScheduleController;
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
        Route::get('/me', function (Request $request) {
            $user = $request->user()->load([
                'roles',
                'employee.position',
                'employee.team.division',
                'employee.manager.user',
            ]);

            return new App\Http\Resources\MeResource($user);
        });
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::post('/attendance/bulk-send', [AttendanceController::class, 'bulkAttendance']);

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/permissions/modules', [RoleController::class, 'permission']);
    Route::apiResource('roles', RoleController::class);
    Route::prefix('users')->group(function () {
        Route::post('/restore/{uuid}', [UserController::class, 'restore']);
        Route::delete('/force-delete/{uuid}', [UserController::class, 'forceDelete']);
        Route::put('terminate-employment/{uuid}', [UserController::class, 'terminateEmployment']);
        Route::put('change-password/{uuid}', [UserController::class, 'changePassword']);
        Route::put('status/{uuid}', [UserController::class, 'status']);
        Route::get('/trashed', [UserController::class, 'getTrashed']);
        Route::post('/upload-profile-photo/{uuid}', [UserController::class, 'uploadProfilePhoto']);
        Route::get('/managers', [UserController::class, 'getManagers']);
        Route::get('/employees-lite', [UserController::class, 'getEmployeesLite']);
    });
    Route::apiResource('users', UserController::class);
    Route::prefix('divisions')->group(function () {
        Route::get('/trashed', [DivisionController::class, 'getTrashed']);
        Route::post('/restore/{uuid}', [DivisionController::class, 'restore']);
        Route::delete('/force-delete/{uuid}', [DivisionController::class, 'forceDelete']);
    });
    Route::apiResource('divisions', DivisionController::class);
    Route::prefix('allowances')->group(function () {
        Route::get('/trashed', [AllowanceController::class, 'getTrashed']);
        Route::post('/restore/{uuid}', [AllowanceController::class, 'restore']);
        Route::delete('/force-delete/{uuid}', [AllowanceController::class, 'forceDelete']);
    });
    Route::apiResource('allowances', AllowanceController::class);
    Route::prefix('positions')->group(function () {
        Route::post('/restore/{uuid}', [PositionController::class, 'restore']);
        Route::delete('/force-delete/{uuid}', [PositionController::class, 'forceDelete']);
        Route::get('/trashed', [PositionController::class, 'getTrashed']);
    });
    Route::apiResource('positions', PositionController::class);
    Route::prefix('settings')->group(function () {
        Route::get('/get', [SettingController::class, 'get']);
        Route::post('/attendance', [SettingController::class, 'updateAttendance']);
        // Route::get('/geo-fencing', [SettingController::class, 'geoFencing']);
        Route::post('/geo_fencing', [SettingController::class, 'updateGeoFencing']);
        Route::post('/general', [SettingController::class, 'updateGeneral']);
    });
    Route::apiResource('attendances', AttendanceDetailController::class)->only('index', 'show');
    Route::apiResource('holidays', HolidayController::class);
    Route::prefix('work_schedules')->group(function () {
        Route::post('/restore/{uuid}', [WorkScheduleController::class, 'restore']);
        Route::delete('/force-delete/{uuid}', [WorkScheduleController::class, 'forceDelete']);
        Route::get('/trashed', [WorkScheduleController::class, 'getTrashed']);
    });
    Route::apiResource('work_schedules', WorkScheduleController::class);
    Route::apiResource('employee_work_schedules', EmployeeWorkScheduleController::class);
    Route::prefix('shift_templates')->group(function () {
        Route::post('/restore/{uuid}', [ShiftTemplateController::class, 'restore']);
        Route::delete('/force-delete/{uuid}', [ShiftTemplateController::class, 'forceDelete']);
        Route::get('/trashed', [ShiftTemplateController::class, 'getTrashed']);
    });
    Route::apiResource('shift_templates', ShiftTemplateController::class);
    Route::apiResource('employee_shift', EmployeeShiftController::class);
});
