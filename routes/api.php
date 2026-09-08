<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register-organization', [AuthController::class, 'registerOrganization']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('me', [ProfileController::class, 'me']);
    Route::put('me', [ProfileController::class, 'update']);
    Route::put('me/password', [ProfileController::class, 'changePassword']);

    Route::post('attendance/toggle', [AttendanceController::class, 'toggle']);
    Route::get('attendance', [AttendanceController::class, 'index']);
    Route::get('attendance/today', [AttendanceController::class, 'today']);
});
