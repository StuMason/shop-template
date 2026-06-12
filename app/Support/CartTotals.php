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
        public int $discount = 0,
        public ?string $discountCode = null,
    ) {}

    public function total(): int
    {
        return max($this->subtotal - $this->discount, 0);
    }

    public function formattedSubtotal(): string
    {
        return Money::format($this->subtotal);
    }

    /**
     * @return array{
     *     subtotal: int,
     *     subtotal_formatted: string,
     *     discount: int,
     *     discount_formatted: string|null,
     *     discount_code: string|null,
     *     total: int,
     *     total_formatted: string,
     *     item_count: int
     * }
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'subtotal_formatted' => $this->formattedSubtotal(),
            'discount' => $this->discount,
            'discount_formatted' => $this->discount > 0 ? Money::format($this->discount) : null,
            'discount_code' => $this->discountCode,
            'total' => $this->total(),
            'total_formatted' => Money::format($this->total()),
            'item_count' => $this->itemCount,
        ];
    }
}
