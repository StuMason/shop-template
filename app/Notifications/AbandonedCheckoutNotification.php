<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\ShopSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class AbandonedCheckoutNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Order $order, public int $stage) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $shopName = app(ShopSettings::class)->name();

        $message = (new MailMessage)
            ->subject($this->stage === 1
                ? "Your order is waiting — {$this->order->number}"
                : "Still want it? Your order expires soon — {$this->order->number}")
            ->greeting('Your order is almost done!')
            ->line("You were one step from finishing order {$this->order->number} — it just needs payment.");

        foreach ($this->order->items as $item) {
            $message->line("- {$item->quantity} × {$item->product_name} — {$item->formattedLineTotal()}");
        }

        return $message
            ->line("Total: {$this->order->formattedTotal()}")
            ->action('Finish your order', URL::signedRoute('checkout.pay', ['order' => $this->order]))
            ->line($this->stage === 1
                ? "Your items are reserved for now. — {$shopName}"
                : "If you don't complete payment, the order will be cancelled and stock released. — {$shopName}");
    }
}
