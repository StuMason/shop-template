<?php

namespace App\Notifications;

use App\Support\Money;
use App\Support\ShopSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  array{revenue: int, orders: int, aov: int, abandonment_rate: float|null, repeat_rate: float|null}  $metrics
     */
    public function __construct(public array $metrics) {}

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

        return (new MailMessage)
            ->subject("{$shopName} — your week in numbers")
            ->greeting('Your week in numbers')
            ->line('Revenue: '.Money::format($this->metrics['revenue']))
            ->line("Orders: {$this->metrics['orders']}")
            ->line('Average order: '.Money::format($this->metrics['aov']))
            ->line('Basket abandonment: '.($this->metrics['abandonment_rate'] !== null ? "{$this->metrics['abandonment_rate']}%" : 'n/a'))
            ->line('Repeat customers: '.($this->metrics['repeat_rate'] !== null ? "{$this->metrics['repeat_rate']}%" : 'n/a'))
            ->action('Open the dashboard', route('admin.dashboard'));
    }
}
