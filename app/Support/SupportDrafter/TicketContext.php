<?php

namespace App\Support\SupportDrafter;

use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Support\ShopSettings;

/**
 * Grounding for the draft: the thread plus the customer's real order data,
 * so the model answers from facts instead of guessing.
 */
class TicketContext
{
    public static function build(Ticket $ticket): string
    {
        $ticket->loadMissing('messages', 'user', 'order.items');

        $settings = app(ShopSettings::class);

        $orders = Order::query()
            ->where(fn ($query) => $query
                ->where('user_id', $ticket->user_id)
                ->orWhere('email', $ticket->user->email))
            ->latest('placed_at')
            ->take(5)
            ->with('items')
            ->get();

        $lines = [
            "Shop: {$settings->name()}",
            "Customer: {$ticket->user->name} <{$ticket->user->email}>",
            "Ticket subject: {$ticket->subject}",
            '',
            '## Recent orders',
        ];

        foreach ($orders as $order) {
            $lines[] = sprintf(
                '- %s | %s | %s | placed %s%s%s',
                $order->number,
                $order->status->value,
                $order->formattedTotal(),
                $order->placed_at->format('j M Y'),
                $order->tracking_number !== null ? " | tracking {$order->carrier} {$order->tracking_number}" : '',
                ' | items: '.$order->items->map(fn ($item) => "{$item->quantity}x {$item->product_name}")->implode(', '),
            );
        }

        if ($orders->isEmpty()) {
            $lines[] = '(no orders found for this customer)';
        }

        $lines[] = '';
        $lines[] = '## Thread';

        foreach ($ticket->messages as $message) {
            /** @var TicketMessage $message */
            $lines[] = ($message->is_staff_reply ? 'STAFF: ' : 'CUSTOMER: ').$message->body;
        }

        return implode("\n", $lines);
    }
}
