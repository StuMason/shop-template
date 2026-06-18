<?php

namespace App\Listeners;

use App\Actions\Orders\CreatePrintfulOrder;
use App\Events\OrderPaid;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * On payment, hand any print-on-demand items to Printful. Queued, so a
 * Printful hiccup never blocks the customer's confirmation.
 */
class SendOrderToPrintful implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly CreatePrintfulOrder $createPrintfulOrder) {}

    public function handle(OrderPaid $event): void
    {
        $this->createPrintfulOrder->handle($event->order);
    }
}
