<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'registerView'])->name('auth.view.register');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

    Route::get('/', [AuthController::class, 'loginView'])->name('auth.view.login');
    Route::post('/', [AuthController::class, 'login'])->name('auth.login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
});
