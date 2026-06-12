<?php

use App\Http\Controllers\Agent\AcpCheckoutController;
use App\Http\Controllers\Agent\AcpFeedController;
use App\Http\Controllers\Agent\X402PaymentController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\Storefront\AddressLookupController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\DownloadController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\PaymentController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Webhooks\GoCardlessWebhookController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use App\Http\Middleware\AuthenticateAcp;
use App\Http\Middleware\DisableInertiaSsr;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('robots.txt', [SiteController::class, 'robots'])->name('robots');
Route::get('sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap');
Route::get('llms.txt', [SiteController::class, 'llms'])->name('agent.llms');
Route::get('llms-full.txt', [SiteController::class, 'llmsFull'])->name('agent.llms-full');
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{product:slug}.md', [SiteController::class, 'productMarkdown'])->name('agent.product-md');
Route::get('products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('pages/{page}', [PageController::class, 'show'])->name('pages.show');

Route::middleware('throttle:30,1')->group(function () {
    Route::get('address-lookup', [AddressLookupController::class, 'suggest'])->name('address-lookup.suggest');
    Route::get('address-lookup/resolve', [AddressLookupController::class, 'resolve'])->name('address-lookup.resolve');
});

Route::get('orders/{order}/downloads/{item}', DownloadController::class)
    ->middleware(['signed', 'throttle:30,1'])
    ->name('orders.download');

Route::get('basket', [CartController::class, 'show'])->name('cart.show');
Route::post('basket/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('basket/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('basket/items/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');
Route::post('basket/discount', [CartController::class, 'applyDiscount'])->name('cart.discount.store');
Route::delete('basket/discount', [CartController::class, 'removeDiscount'])->name('cart.discount.destroy');

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

// x402: agents settle an order in USDC via the facilitator. Signed URL =
// the capability to pay; humans keep using the normal gateway.
Route::get('agent/orders/{order}/pay', X402PaymentController::class)
    ->middleware(['signed', 'throttle:30,1'])
    ->name('agent.pay.x402');

// Agentic Commerce Protocol — the surface AI shopping agents speak.
Route::prefix('acp')->middleware([AuthenticateAcp::class, 'throttle:60,1'])->name('acp.')->group(function () {
    Route::get('feed', AcpFeedController::class)->name('feed');
    Route::post('checkout_sessions', [AcpCheckoutController::class, 'store'])->name('sessions.store');
    Route::get('checkout_sessions/{session}', [AcpCheckoutController::class, 'show'])->name('sessions.show');
    Route::post('checkout_sessions/{session}', [AcpCheckoutController::class, 'update'])->name('sessions.update');
    Route::post('checkout_sessions/{session}/complete', [AcpCheckoutController::class, 'complete'])->name('sessions.complete');
    Route::post('checkout_sessions/{session}/cancel', [AcpCheckoutController::class, 'cancel'])->name('sessions.cancel');
});
