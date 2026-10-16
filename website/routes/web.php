<?php

use App\Http\Controllers\Identity\RegisterUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/register', RegisterUserController::class)
    ->middleware('guest')
    ->name('register.store');
