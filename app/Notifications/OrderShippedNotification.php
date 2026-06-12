<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class OrderShippedNotification extends Notification implements ShouldQueue
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
        $url = $notifiable instanceof AnonymousNotifiable
            ? URL::signedRoute('orders.guest-show', ['order' => $this->order])
            : route('account.orders.show', $this->order);

        return (new MailMessage)
            ->subject("Your order is on its way — {$this->order->number}")
            ->line("Order {$this->order->number} has been shipped to {$this->order->shipping_address['postcode']}.")
            ->action('View your order', $url);
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
            'message' => "Order {$this->order->number} has shipped.",
        ];
    }
}
