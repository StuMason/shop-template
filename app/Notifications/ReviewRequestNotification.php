<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\ShopSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ReviewRequestNotification extends Notification implements ShouldQueue
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $shopName = app(ShopSettings::class)->name();

        $message = (new MailMessage)
            ->subject("How was it? — order {$this->order->number}")
            ->greeting('How did we do?')
            ->line('A quick rating helps other shoppers (and us) enormously. Your links:');

        foreach ($this->order->items as $item) {
            $product = $item->variant?->product;

            if ($product === null) {
                continue;
            }

            $message->line(sprintf(
                '- %s: %s',
                $item->product_name,
                URL::signedRoute('reviews.create', ['order' => $this->order, 'product' => $product]),
            ));
        }

        return $message->line("Two clicks, honest stars. Thank you! — {$shopName}");
    }
}
