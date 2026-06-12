<?php

namespace App\Support;

/**
 * Cart money summary in integer pence with server-side formatted strings.
 */
readonly class CartTotals
{
    public function __construct(
        public int $subtotal,
        public int $itemCount,
    ) {}

    public function formattedSubtotal(): string
    {
        return Money::format($this->subtotal);
    }

    /**
     * @return array{subtotal: int, subtotal_formatted: string, item_count: int}
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'subtotal_formatted' => $this->formattedSubtotal(),
            'item_count' => $this->itemCount,
        ];
    }
}
