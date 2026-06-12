<?php

use App\Http\Middleware\DisableInertiaSsr;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', DisableInertiaSsr::class])
    ->prefix('account')
    ->name('account.')
    ->group(function () {
        Route::inertia('/', 'account/dashboard')->name('dashboard');
    });
