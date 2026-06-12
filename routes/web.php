<?php

use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\PaymentController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Webhooks\GoCardlessWebhookController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use App\Http\Middleware\DisableInertiaSsr;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('basket', [CartController::class, 'show'])->name('cart.show');
Route::post('basket/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('basket/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('basket/items/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');

Route::middleware(DisableInertiaSsr::class)->group(function () {
    Route::get('checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('checkout/pay/{order}', [PaymentController::class, 'show'])
        ->middleware('signed')
        ->name('checkout.pay');
    Route::post('checkout/pay/{order}', [PaymentController::class, 'store'])->name('checkout.pay.start');
    Route::get('checkout/return/{payment:idempotency_key}', [PaymentController::class, 'returnFromGateway'])->name('checkout.return');
    Route::get('checkout/complete/{order}', [PaymentController::class, 'complete'])
        ->middleware('signed')
        ->name('checkout.complete');

    Route::get('orders/guest/{order}', [PaymentController::class, 'complete'])
        ->middleware('signed')
        ->name('orders.guest-show');
});

Route::post('webhooks/payments', PaymentWebhookController::class)
    ->middleware('throttle:webhooks')
    ->name('webhooks.payments');

Route::post('webhooks/gocardless', GoCardlessWebhookController::class)
    ->middleware('throttle:webhooks')
    ->name('webhooks.gocardless');

Route::middleware(['auth', 'verified', DisableInertiaSsr::class])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/account.php';
require __DIR__.'/admin.php';
