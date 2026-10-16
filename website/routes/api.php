<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\EnsureApiAccountCanAccess;
use Illuminate\Support\Facades\Route;

// Authentication Api
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1');

Route::post('/auth/refresh', [AuthController::class, 'refresh'])
    ->middleware('throttle:6,1');

Route::middleware([
    'auth:api',
    EnsureApiAccountCanAccess::class,
])->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
