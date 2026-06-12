<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductMediaController;
use App\Http\Controllers\Admin\ProductOptionController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\DisableInertiaSsr;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin|staff', DisableInertiaSsr::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::inertia('/', 'admin/dashboard')->name('dashboard');

        Route::resource('products', ProductController::class)->except('show');
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
        Route::put('products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->name('products.variants.update');
        Route::delete('products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('products.variants.destroy');

        Route::post('products/{product}/options', [ProductOptionController::class, 'store'])->name('products.options.store');
        Route::put('products/{product}/options/{option}', [ProductOptionController::class, 'update'])->name('products.options.update');
        Route::delete('products/{product}/options/{option}', [ProductOptionController::class, 'destroy'])->name('products.options.destroy');

        Route::post('products/{product}/media', [ProductMediaController::class, 'store'])->name('products.media.store');
        Route::delete('products/{product}/media/{mediaId}', [ProductMediaController::class, 'destroy'])->name('products.media.destroy');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order:id}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order:id}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('payments/{payment}/refunds', [OrderController::class, 'storeRefund'])->name('payments.refunds.store');

        Route::get('support', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('support/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::post('support/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
        Route::patch('support/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');

        Route::get('shipping', [ShippingController::class, 'index'])->name('shipping.index');
        Route::post('shipping/zones', [ShippingController::class, 'storeZone'])->name('shipping.zones.store');
        Route::put('shipping/zones/{zone}', [ShippingController::class, 'updateZone'])->name('shipping.zones.update');
        Route::delete('shipping/zones/{zone}', [ShippingController::class, 'destroyZone'])->name('shipping.zones.destroy');
        Route::post('shipping/zones/{zone}/methods', [ShippingController::class, 'storeMethod'])->name('shipping.methods.store');
        Route::put('shipping/zones/{zone}/methods/{method}', [ShippingController::class, 'updateMethod'])->name('shipping.methods.update');
        Route::delete('shipping/zones/{zone}/methods/{method}', [ShippingController::class, 'destroyMethod'])->name('shipping.methods.destroy');

        Route::middleware('role:admin')->group(function () {
            Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::patch('users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        });
    });
