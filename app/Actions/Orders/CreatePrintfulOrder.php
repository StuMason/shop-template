<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Printful\PrintfulClient;

/**
 * Push the print-on-demand items of a paid order to Printful for fulfilment.
 * Only items whose variant carries a printful_variant_id are sent, so mixed
 * baskets (POD + digital + plain physical) work; the rest stay manual.
 */
class CreatePrintfulOrder
{
    public function __construct(private readonly PrintfulClient $printful) {}

    public function handle(Order $order): void
    {
        if (! $this->printful->enabled() || $order->printful_order_id !== null) {
            return;
        }

        $order->loadMissing('items.variant');

        $items = $order->items
            ->filter(fn (OrderItem $item): bool => $item->variant?->printful_variant_id !== null)
            ->map(fn (OrderItem $item): array => [
                'sync_variant_id' => (int) $item->variant->printful_variant_id,
                'quantity' => $item->quantity,
            ])
            ->values();

        if ($items->isEmpty()) {
            return;
        }

        $address = $order->shipping_address;

        $recipient = array_filter([
            'name' => $address['name'] ?? null,
            'address1' => $address['line1'] ?? null,
            'address2' => $address['line2'] ?? null,
            'city' => $address['city'] ?? null,
            'state_code' => $address['county'] ?? null,
            'country_code' => $address['country'] ?? null,
            'zip' => $address['postcode'] ?? null,
            'phone' => $address['phone'] ?? null,
            'email' => $order->email,
        ], fn ($value): bool => $value !== null && $value !== '');

        $result = $this->printful->createOrder([
            'external_id' => $order->number,
            'recipient' => $recipient,
            'items' => $items->all(),
        ], confirm: (bool) config('services.printful.auto_confirm'));

        if (isset($result['id'])) {
            $order->update(['printful_order_id' => $result['id']]);
        }
    }
}
