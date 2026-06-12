<?php

namespace App\Support;

/**
 * UK consumer pricing: amounts are always VAT-inclusive. This extracts the
 * VAT contained in an inclusive amount at a given percentage rate.
 */
class Vat
{
    public static function contained(int $inclusiveAmount, float $rate): int
    {
        if ($rate <= 0) {
            return 0;
        }

        return (int) round($inclusiveAmount * $rate / (100 + $rate));
    }
}
