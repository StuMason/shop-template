<?php

namespace App\Support;

use Illuminate\Support\Number;

/**
 * Money lives as integer minor units (pence) everywhere. Formatting happens
 * server-side only, so SSR and client hydration always agree.
 */
class Money
{
    public static function format(int $minorUnits, ?string $currency = null): string
    {
        $currency ??= app(ShopSettings::class)->currency();

        return (string) Number::currency($minorUnits / 100, in: $currency);
    }
}
