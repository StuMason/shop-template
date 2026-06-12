<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Abandoned = 'abandoned';
    case Refunded = 'refunded';

    public function isSettled(): bool
    {
        return $this !== self::Pending;
    }
}
