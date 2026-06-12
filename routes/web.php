<?php

use App\Http\Middleware\DisableInertiaSsr;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', DisableInertiaSsr::class])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/account.php';
require __DIR__.'/admin.php';
