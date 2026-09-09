<?php

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
        });
    });
});

Route::get('/', fn () => redirect()->route('portal.login'));
