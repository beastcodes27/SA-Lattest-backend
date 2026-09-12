<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PackagesController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PromoController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SystemController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register-organization', [AuthController::class, 'registerOrganization']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::get('packages', [PackagesController::class, 'publicList']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('me', [ProfileController::class, 'me']);
    Route::put('me', [ProfileController::class, 'update']);
    Route::post('me/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::put('me/password', [ProfileController::class, 'changePassword']);
    Route::put('me/password/force', [ProfileController::class, 'forceChangePassword']);

    Route::post('attendance/toggle', [AttendanceController::class, 'toggle']);
    Route::post('attendance/sync', [AttendanceController::class, 'sync']);
    Route::get('attendance', [AttendanceController::class, 'index']);
    Route::get('attendance/today', [AttendanceController::class, 'today']);
});

Route::middleware(['auth:sanctum', 'org-admin'])->prefix('admin')->group(function () {
    Route::get('stats', [AdminController::class, 'stats']);
    Route::get('employees', [AdminController::class, 'employees']);
    Route::post('employees', [AdminController::class, 'storeEmployee']);
    Route::get('employees/next', [AdminController::class, 'nextEmployeeId']);
    Route::patch('employees/{employee}', [AdminController::class, 'toggleEmployee']);
    Route::post('employees/{employee}/reset-password', [AdminController::class, 'resetEmployeePassword']);
    Route::get('reports', [AdminController::class, 'reports']);
    Route::get('branches', [AdminController::class, 'branches']);
    Route::post('branches', [AdminController::class, 'storeBranch']);
    Route::patch('organization', [AdminController::class, 'updateOrganization']);
    Route::post('promo/redeem', [PromoController::class, 'redeem']);
    Route::get('promos/available', [PromoController::class, 'available']);
});

Route::middleware(['auth:sanctum', 'org-admin'])->prefix('admin')->group(function () {
    Route::get('subscription', [SubscriptionController::class, 'details']);
    Route::post('subscription/upgrade', [SubscriptionController::class, 'upgrade']);
    Route::post('subscription/cancel', [SubscriptionController::class, 'cancel']);
});

Route::middleware(['auth:sanctum', 'system-admin'])->prefix('system')->group(function () {
    Route::get('stats', [SystemController::class, 'stats']);
    Route::get('organizations', [SystemController::class, 'organizations']);
    Route::post('organizations/{organization}/approve', [SystemController::class, 'approve']);
    Route::post('organizations/{organization}/status', [SystemController::class, 'setStatus']);
});
