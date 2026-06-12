<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\ShopSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class OrderPaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Order $order) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $shopName = app(ShopSettings::class)->name();

        $url = $notifiable instanceof AnonymousNotifiable
            ? URL::signedRoute('orders.guest-show', ['order' => $this->order])
            : route('account.orders.show', $this->order);

        $message = (new MailMessage)
            ->subject("Order confirmed — {$this->order->number}")
            ->greeting('Thanks for your order!')
            ->line("We've received your payment for order {$this->order->number}.");

        foreach ($this->order->items as $item) {
            $message->line("- {$item->quantity} × {$item->product_name} ({$item->variant_name}) — {$item->formattedLineTotal()}");
        }

        return $message
            ->line("Shipping: {$this->order->formattedShippingTotal()}")
            ->line("Total: {$this->order->formattedTotal()}")
            ->action('View your order', $url)
            ->line("We'll let you know as soon as it ships. — {$shopName}");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_number' => $this->order->number,
            'total' => $this->order->formattedTotal(),
            'message' => "Order {$this->order->number} confirmed.",
        ];
    }
}
