<?php

use App\Presentation\Api\V1\Controllers\AuthTokenController;
use App\Presentation\Api\V1\Middleware\AuthenticateWithJwt;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/token', [AuthTokenController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('auth.token');

    Route::post('/auth/refresh', [AuthTokenController::class, 'refresh'])
        ->middleware('throttle:20,1')
        ->name('auth.refresh');

    Route::middleware(AuthenticateWithJwt::class)->group(function () {
        Route::post('/auth/logout', [AuthTokenController::class, 'destroy'])->name('auth.logout');
        Route::get('/auth/me', [AuthTokenController::class, 'me'])->name('auth.me');
    });
});
