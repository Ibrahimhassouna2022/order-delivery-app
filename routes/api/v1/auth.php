<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('api.v1.auth.')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:auth-register')
        ->name('register');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth-login')
        ->name('login');
    Route::post('/guest', [AuthController::class, 'guest'])
        ->middleware('throttle:auth-register')
        ->name('guest');
    Route::post('/google', [AuthController::class, 'google'])
        ->middleware('throttle:auth-register')
        ->name('google');
    Route::post('/apple', [AuthController::class, 'apple'])
        ->middleware('throttle:auth-register')
        ->name('apple');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/user', [AuthController::class, 'user'])->name('user');
    });
});
