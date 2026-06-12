<?php

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Events\Login;

function publishedVariant(int $stock = 10, int $price = 1000)
{
    return Product::factory()
        ->published()
        ->withDefaultVariant(price: $price, stock: $stock)
        ->create()
        ->variants()
        ->first();
}

it('adds a product to a guest basket and shares it with the frontend', function () {
    $variant = publishedVariant(price: 1500);

    $this->post(route('cart.items.store'), [
        'variant_id' => $variant->id,
        'quantity' => 2,
    ])->assertRedirect();

    expect(session('cart_token'))->not->toBeNull();

    $this->get(route('cart.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/basket')
            ->where('basket.item_count', 2)
            ->where('basket.subtotal_formatted', '£30.00'));
});

it('merges quantities when adding the same variant twice', function () {
    $variant = publishedVariant();

    $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 1]);
    $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 2]);

    $cart = Cart::query()->firstWhere('token', session('cart_token'));

    expect($cart->items)->toHaveCount(1)
        ->and($cart->items->first()->quantity)->toBe(3);
});

it('rejects adding more than the available stock', function () {
    $variant = publishedVariant(stock: 2);

    $this->from(route('products.show', $variant->product))
        ->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 3])
        ->assertRedirect(route('products.show', $variant->product))
        ->assertSessionHasErrors('quantity');
});

it('refuses to add unpublished products', function () {
    $draft = Product::factory()->withDefaultVariant()->create();

    $this->post(route('cart.items.store'), [
        'variant_id' => $draft->variants->first()->id,
    ])->assertNotFound();
});

it('updates a line quantity and removes it at zero', function () {
    $variant = publishedVariant();

    $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 1]);

    $cart = Cart::query()->firstWhere('token', session('cart_token'));
    $item = $cart->items->first();

    $this->patch(route('cart.items.update', $item), ['quantity' => 5])->assertRedirect();
    expect($item->fresh()->quantity)->toBe(5);

    $this->patch(route('cart.items.update', $item), ['quantity' => 0])->assertRedirect();
    expect($item->fresh())->toBeNull();
});

it('removes a line from the basket', function () {
    $variant = publishedVariant();

    $this->post(route('cart.items.store'), ['variant_id' => $variant->id]);

    $cart = Cart::query()->firstWhere('token', session('cart_token'));
    $item = $cart->items->first();

    $this->delete(route('cart.items.destroy', $item))->assertRedirect();

    expect($cart->items()->count())->toBe(0);
});

it('cannot modify items belonging to a different basket', function () {
    $variant = publishedVariant();
    $otherCart = Cart::factory()->create();
    $foreignItem = $otherCart->items()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->post(route('cart.items.store'), ['variant_id' => $variant->id]);

    $this->patch(route('cart.items.update', $foreignItem), ['quantity' => 5])
        ->assertNotFound();

    expect($foreignItem->fresh()->quantity)->toBe(1);
});

it('merges the guest basket into the user basket on login', function () {
    $user = User::factory()->create();
    $variant = publishedVariant(stock: 10);

    $userCart = Cart::factory()->forUser($user)->create();
    $userCart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 2]);

    $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 3]);
    $guestCart = Cart::query()->firstWhere('token', session('cart_token'));

    event(new Login('web', $user, false));

    expect($userCart->items()->first()->quantity)->toBe(5)
        ->and($guestCart->fresh()->status)->toBe(CartStatus::Abandoned)
        ->and(session('cart_token'))->toBeNull();
});

it('adopts the guest basket on login when the user has none', function () {
    $user = User::factory()->create();
    $variant = publishedVariant();

    $this->post(route('cart.items.store'), ['variant_id' => $variant->id]);
    $guestCart = Cart::query()->firstWhere('token', session('cart_token'));

    event(new Login('web', $user, false));

    expect($guestCart->fresh()->user_id)->toBe($user->id);
});

it('caps merged quantities at available stock', function () {
    $user = User::factory()->create();
    $variant = publishedVariant(stock: 4);

    $userCart = Cart::factory()->forUser($user)->create();
    $userCart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 3]);

    $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 3]);

    event(new Login('web', $user, false));

    expect($userCart->items()->first()->quantity)->toBe(4);
});
