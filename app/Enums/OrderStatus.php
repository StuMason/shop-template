<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    /**
     * The order lifecycle, encoded in one place.
     */
    public function canTransitionTo(OrderStatus $target): bool
    {
        return in_array($target, match ($this) {
            self::Pending => [self::Paid, self::Cancelled],
            self::Paid => [self::Processing, self::Shipped, self::Cancelled, self::Refunded],
            self::Processing => [self::Shipped, self::Cancelled, self::Refunded],
            self::Shipped => [self::Delivered, self::Refunded],
            self::Delivered => [self::Refunded],
            self::Cancelled, self::Refunded => [],
        }, true);
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
