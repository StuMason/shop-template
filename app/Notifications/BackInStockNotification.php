<?php

namespace App\Notifications;

use App\Models\ProductVariant;
use App\Support\ShopSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackInStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public ProductVariant $variant) {}

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
        $product = $this->variant->product;
        $shopName = app(ShopSettings::class)->name();
        $variantName = $this->variant->displayName();

        return (new MailMessage)
            ->subject("Back in stock: {$product->name}")
            ->greeting("It's back!")
            ->line(sprintf(
                '%s%s is back in stock — and you asked us to tell you.',
                $product->name,
                $variantName !== 'Default' ? " ({$variantName})" : '',
            ))
            ->action('Grab it now', route('products.show', $product->slug))
            ->line("Popular items go fast. — {$shopName}");
    }
}
