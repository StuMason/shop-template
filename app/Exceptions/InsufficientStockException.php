<?php

namespace App\Exceptions;

use App\Models\ProductVariant;
use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(public readonly ProductVariant $variant, int $requested)
    {
        parent::__construct(
            $variant->stock <= 0
                ? "{$variant->sku} is out of stock."
                : "Only {$variant->stock} of {$variant->sku} left (requested {$requested}).",
        );
    }
}
