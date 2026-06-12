<?php

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\NotificationController;
use App\Http\Controllers\Account\OrderController;
use App\Http\Controllers\Account\TicketController;
use App\Http\Middleware\DisableInertiaSsr;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', DisableInertiaSsr::class])
    ->prefix('account')
    ->name('account.')
    ->group(function () {
        Route::inertia('/', 'account/dashboard')->name('dashboard');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');

        Route::get('addresses', [AddressController::class, 'index'])->name('addresses.index');
        Route::post('addresses', [AddressController::class, 'store'])->name('addresses.store');
        Route::put('addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
        Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

        Route::get('support', [TicketController::class, 'index'])->name('tickets.index');
        Route::post('support', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('support/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::post('support/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    });
