<?php

use App\Http\Controllers\Identity\RegisterUserController;
use App\Http\Controllers\Identity\ResendVerificationEmailController;
use App\Http\Controllers\Identity\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/register', RegisterUserController::class)
    ->middleware('guest')
    ->name('register.store');

Route::get('/email/verify/{user}', VerifyEmailController::class)
    ->middleware('signed')
    ->name('identity.email.verify');

Route::post(
    '/email/verification-notification',
    ResendVerificationEmailController::class,
)
    ->middleware('throttle:3,1')
    ->name('identity.email.resend');
