<?php

namespace App\Actions\Checkout;

use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Events\LowStockDetected;
use App\Events\OrderPlaced;
use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Support\OrderNumber;
use App\Support\ShopSettings;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Turns a cart into a pending order: locks variants, decrements stock,
 * snapshots lines and addresses. Stock is taken at order creation (open
 * banking authorisation takes minutes — decrementing at payment invites
 * oversell) and restocked on cancellation or abandonment.
 *
 * Idempotent: the unique orders.cart_id means a double submit returns the
 * already-created order.
 */
class CreateOrderFromCart
{
    public function __construct(
        private readonly ShopSettings $settings,
    ) {}

    /**
     * @throws InsufficientStockException
     */
    public function handle(Cart $cart, CheckoutData $data): Order
    {
        $existing = Order::query()->firstWhere('cart_id', $cart->id);

        if ($existing !== null) {
            return $existing;
        }

        $cart->loadMissing('items.variant.product', 'items.variant.optionValues');

        if ($cart->items->isEmpty()) {
            throw new InvalidArgumentException('Cannot check out an empty basket.');
        }

        $shippingMethod = ShippingMethod::query()
            ->active()
            ->whereKey($data->shippingMethodId)
            ->firstOrFail();

        try {
            return DB::transaction(function () use ($cart, $data, $shippingMethod): Order {
                $lowStock = [];

                // Lock and decrement stock inside the transaction.
                foreach ($cart->items as $item) {
                    $variant = ProductVariant::query()
                        ->whereKey($item->product_variant_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($variant->stock < $item->quantity) {
                        throw new InsufficientStockException($variant, $item->quantity);
                    }

                    $variant->decrement('stock', $item->quantity);

                    if ($variant->refresh()->isLowStock()) {
                        $lowStock[] = $variant;
                    }
                }

                $subtotal = $cart->items->sum(fn ($item): int => $item->lineTotal());
                $shippingTotal = $shippingMethod->priceFor($subtotal);

                $order = Order::create([
                    'number' => OrderNumber::generate(),
                    'user_id' => $cart->user_id,
                    'cart_id' => $cart->id,
                    'email' => $data->email,
                    'status' => OrderStatus::Pending,
                    'currency' => $this->settings->currency(),
                    'subtotal' => $subtotal,
                    'shipping_total' => $shippingTotal,
                    'total' => $subtotal + $shippingTotal,
                    'shipping_method_name' => $shippingMethod->name,
                    'shipping_address' => $data->shippingAddress,
                    'billing_address' => $data->billingAddressOrShipping(),
                    'customer_note' => $data->customerNote,
                    'placed_at' => now(),
                ]);

                foreach ($cart->items as $item) {
                    $order->items()->create([
                        'product_variant_id' => $item->variant->id,
                        'product_name' => $item->variant->product->name,
                        'variant_name' => $item->variant->displayName(),
                        'sku' => $item->variant->sku,
                        'unit_price' => $item->variant->price,
                        'quantity' => $item->quantity,
                        'line_total' => $item->lineTotal(),
                    ]);
                }

                $cart->update(['status' => CartStatus::Converted]);

                OrderPlaced::dispatch($order);

                foreach ($lowStock as $variant) {
                    LowStockDetected::dispatch($variant);
                }

                return $order;
            });
        } catch (UniqueConstraintViolationException) {
            // A concurrent submit for the same cart won the race.
            return Order::query()->where('cart_id', $cart->id)->firstOrFail();
        }
    }
}
