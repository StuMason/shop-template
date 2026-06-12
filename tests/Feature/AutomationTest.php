<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\StockNotification;
use App\Models\User;
use App\Notifications\AbandonedCheckoutNotification;
use App\Notifications\BackInStockNotification;
use App\Notifications\ReviewRequestNotification;
use App\Notifications\WeeklyDigestNotification;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('sends staged recovery emails for unpaid orders and stops after payment', function () {
    Notification::fake();

    $stale = Order::factory()->create(['placed_at' => now()->subHours(2)]);
    $veryStale = Order::factory()->create(['placed_at' => now()->subDays(2)]);
    $fresh = Order::factory()->create(['placed_at' => now()->subMinutes(10)]);
    $paid = Order::factory()->paid()->create(['placed_at' => now()->subHours(3)]);

    $this->artisan('shop:send-recovery-emails')->assertSuccessful();

    Notification::assertSentOnDemandTimes(AbandonedCheckoutNotification::class, 2);
    expect($stale->fresh()->recovery_stage)->toBe(1)
        ->and($veryStale->fresh()->recovery_stage)->toBe(2)
        ->and($fresh->fresh()->recovery_stage)->toBe(0)
        ->and($paid->fresh()->recovery_stage)->toBe(0);

    // Second run: stale graduates to stage 2; nothing re-sent at same stage.
    $this->travel(23)->hours();
    $this->artisan('shop:send-recovery-emails')->assertSuccessful();
    expect($stale->fresh()->recovery_stage)->toBe(2);
});

it('notifies the waitlist when stock returns', function () {
    Notification::fake();

    $product = Product::factory()->published()->withDefaultVariant(stock: 0)->create();
    $variant = $product->variants->first();

    $this->post(route('stock-notifications.store'), [
        'email' => 'wanting@example.com',
        'variant_id' => $variant->id,
    ])->assertRedirect();

    expect(StockNotification::query()->count())->toBe(1);

    $variant->update(['stock' => 5]);

    Notification::assertSentOnDemand(
        BackInStockNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'wanting@example.com',
    );

    expect(StockNotification::query()->sole()->notified_at)->not->toBeNull();

    // Restocking again doesn't re-notify.
    $variant->update(['stock' => 10]);
    Notification::assertSentOnDemandTimes(BackInStockNotification::class, 1);
});

it('requests reviews after delivery and accepts verified submissions', function () {
    Notification::fake();

    $product = Product::factory()->published()->withDefaultVariant()->create();
    $order = Order::factory()->paid()->create([
        'status' => OrderStatus::Delivered,
        'delivered_at' => now()->subDays(4),
    ]);
    $order->items()->create([
        'product_variant_id' => $product->variants->first()->id,
        'product_name' => $product->name,
        'variant_name' => 'Default',
        'sku' => $product->variants->first()->sku,
        'unit_price' => 1000,
        'quantity' => 1,
        'line_total' => 1000,
    ]);

    $this->artisan('shop:send-review-requests')->assertSuccessful();
    Notification::assertSentOnDemand(ReviewRequestNotification::class);
    expect($order->fresh()->review_requested_at)->not->toBeNull();

    // The signed link accepts a review and the PDP gains AggregateRating data.
    $url = URL::signedRoute('reviews.store', ['order' => $order, 'product' => $product]);
    $this->post($url, ['rating' => 5, 'name' => 'Riley', 'body' => 'Cracking mug.'])
        ->assertRedirect(route('products.show', $product->slug));

    expect(Review::query()->sole()->rating)->toBe(5);

    $this->get(route('products.show', $product->slug))
        ->assertInertia(fn ($page) => $page
            ->where('reviews.count', 1)
            ->where('reviews.average', 5)
            ->where('reviews.items.0.name', 'Riley'));

    // Unsigned submissions are refused.
    $this->post(route('reviews.store', ['order' => $order, 'product' => $product]), [
        'rating' => 1, 'name' => 'Spam',
    ])->assertForbidden();
});

it('refuses reviews for products not in the order', function () {
    $bought = Product::factory()->published()->withDefaultVariant()->create();
    $other = Product::factory()->published()->withDefaultVariant()->create();
    $order = Order::factory()->paid()->create();
    $order->items()->create([
        'product_variant_id' => $bought->variants->first()->id,
        'product_name' => $bought->name,
        'variant_name' => 'Default',
        'sku' => $bought->variants->first()->sku,
        'unit_price' => 1000,
        'quantity' => 1,
        'line_total' => 1000,
    ]);

    $url = URL::signedRoute('reviews.store', ['order' => $order, 'product' => $other]);
    $this->post($url, ['rating' => 5, 'name' => 'Nope'])->assertForbidden();
});

it('lets admins hide reviews and hidden reviews leave the PDP', function () {
    $this->seed(RolesSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $product = Product::factory()->published()->withDefaultVariant()->create();
    $review = Review::factory()->create(['product_id' => $product->id]);

    $this->actingAs($admin)
        ->put(route('admin.reviews.update', $review), ['is_published' => false])
        ->assertRedirect();

    expect($review->fresh()->is_published)->toBeFalse();

    $this->get(route('products.show', $product->slug))
        ->assertInertia(fn ($page) => $page->where('reviews.count', 0));
});

it('computes the weekly digest metrics', function () {
    $this->seed(RolesSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Notification::fake();

    Order::factory()->paid()->create(['total' => 4000, 'email' => 'a@example.com', 'placed_at' => now()->subDay()]);
    Order::factory()->paid()->create(['total' => 2000, 'email' => 'a@example.com', 'placed_at' => now()->subDay()]);

    $this->artisan('shop:send-weekly-digest')->assertSuccessful();

    Notification::assertSentTo($admin, WeeklyDigestNotification::class,
        fn (WeeklyDigestNotification $notification) => $notification->metrics['revenue'] === 6000
            && $notification->metrics['orders'] === 2
            && $notification->metrics['aov'] === 3000
            && $notification->metrics['repeat_rate'] === 100.0,
    );
});

it('shows the five metrics on the admin dashboard', function () {
    $this->seed(RolesSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Order::factory()->paid()->create(['total' => 5000, 'placed_at' => now()->subDay()]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('month.revenue', '£50.00')
            ->where('month.orders', 1));
});
