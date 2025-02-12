<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AjustesController;
use App\Http\Controllers\ApiController;

Route::middleware([
    'web',
])->group(function () {

    //RUTAS DE DASHBOARD
    Route::middleware('auth')->controller(DashboardController::class)->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::get(
            '/',
            'dashboard'
        );
    });
    Route::middleware('auth')->controller(ApiController::class)->group(function () {

        Route::get('config', 'config')->name('config');
        Route::get('logs', 'index')->name('index');
    });
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    Route::get('ajustes/cuenta', [AjustesController::class, 'cuenta'])->name('ajustes.cuenta');
});
