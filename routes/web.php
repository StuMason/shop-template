<?php

use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
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

Route::middleware(['auth', 'verified', DisableInertiaSsr::class])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/account.php';
require __DIR__.'/admin.php';
