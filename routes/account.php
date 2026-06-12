<?php

use App\Http\Controllers\Account\OrderController;
use App\Http\Middleware\DisableInertiaSsr;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', DisableInertiaSsr::class])
    ->prefix('account')
    ->name('account.')
    ->group(function () {
        Route::inertia('/', 'account/dashboard')->name('dashboard');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    });
