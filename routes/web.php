<?php

use App\Http\Controllers\Api\PackagesController;
use App\Http\Controllers\Api\PromoManagerController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->group(function () {
    Route::get('/', [PortalController::class, 'showLogin'])->name('portal.login');
    Route::post('/login', [PortalController::class, 'login'])->name('portal.login.post');
    Route::post('/logout', [PortalController::class, 'logout'])->name('portal.logout');

    Route::middleware('portal.system')->group(function () {
        Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('portal.dashboard');

        Route::prefix('api')->group(function () {
            Route::get('stats', [SystemController::class, 'stats']);
            Route::get('organizations', [SystemController::class, 'organizations']);
            Route::get('organizations/{organization}', [SystemController::class, 'organization']);
            Route::post('organizations/{organization}/approve', [SystemController::class, 'approve']);
            Route::post('organizations/{organization}/status', [SystemController::class, 'setStatus']);
            Route::post('organizations/{organization}/subscription', [SystemController::class, 'updateSubscription']);

            Route::get('packages', [PackagesController::class, 'systemIndex']);
            Route::put('packages/{package}', [PackagesController::class, 'update']);

            Route::get('promos', [PromoManagerController::class, 'index']);
            Route::post('promos', [PromoManagerController::class, 'store']);
            Route::put('promos/{promo}', [PromoManagerController::class, 'update']);
        });
    });
});

Route::get('/', [PortalController::class, 'showLogin'])->name('home');
