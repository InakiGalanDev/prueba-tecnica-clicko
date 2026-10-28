<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TopEmailDomainController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('users/email-domains/top', TopEmailDomainController::class)->name('users.email-domains.top');
        Route::apiResource('users', UserController::class);
    });
});
