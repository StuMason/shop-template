<?php

namespace App\Actions\Cart;

use App\Models\Cart;

class RemoveDiscount
{
    public function handle(Cart $cart): void
    {
        $cart->update(['discount_id' => null]);
    }
}
