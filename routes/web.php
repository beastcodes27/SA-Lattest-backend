<?php

use App\Http\Controllers\Api\AppVersionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PackagesController;
use App\Http\Controllers\Api\PromoManagerController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->group(function () {
    Route::get('/', [PortalController::class, 'showLogin'])->name('portal.login');
    Route::post('/login', [PortalController::class, 'login'])->middleware('throttle:auth')->name('portal.login.post');
    Route::post('/logout', [PortalController::class, 'logout'])->name('portal.logout');

    Route::middleware('portal.system')->group(function () {
        Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('portal.dashboard');

        Route::prefix('api')->group(function () {
            Route::get('stats', [SystemController::class, 'stats']);
            Route::get('organizations', [SystemController::class, 'organizations']);
            Route::get('organizations/{organization}', [SystemController::class, 'organization']);
            Route::post('organizations/{organization}/approve', [SystemController::class, 'approve']);
            Route::post('organizations/{organization}/reject', [SystemController::class, 'reject']);
            Route::post('organizations/{organization}/status', [SystemController::class, 'setStatus']);
            Route::post('organizations/{organization}/subscription', [SystemController::class, 'updateSubscription']);
            Route::delete('organizations/{organization}', [SystemController::class, 'destroyOrganization']);

            Route::get('admins', [SystemController::class, 'minorAdmins']);
            Route::post('admins', [SystemController::class, 'storeMinorAdmin']);
            Route::patch('admins/{user}', [SystemController::class, 'toggleMinorAdmin']);
            Route::post('admins/{user}/reset-password', [SystemController::class, 'resetMinorAdminPassword']);
            Route::delete('admins/{user}', [SystemController::class, 'deleteMinorAdmin']);

            Route::get('packages', [PackagesController::class, 'systemIndex']);
            Route::post('packages', [PackagesController::class, 'store']);
            Route::put('packages/{package}', [PackagesController::class, 'update']);

            Route::get('promos', [PromoManagerController::class, 'index']);
            Route::post('promos', [PromoManagerController::class, 'store']);
            Route::put('promos/{promo}', [PromoManagerController::class, 'update']);
            Route::delete('promos/{promo}', [PromoManagerController::class, 'destroy']);

            Route::get('notifications/history', [NotificationController::class, 'systemHistory']);
            Route::post('notifications/broadcast', [NotificationController::class, 'systemBroadcast']);
            Route::post('notifications/sms', [NotificationController::class, 'systemSms']);
            Route::delete('notifications/{id}', [NotificationController::class, 'destroyBroadcast']);

            Route::get('app-version', [AppVersionController::class, 'adminShow']);
            Route::post('app-version', [AppVersionController::class, 'adminUpdate']);

            Route::post('password', [SystemController::class, 'changePassword']);
        });
    });
});

Route::get('/', [PortalController::class, 'showLogin'])->name('home');
