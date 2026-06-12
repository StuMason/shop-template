<?php

namespace App\Mcp\Concerns;

use App\Actions\Cart\ResolveCart;
use App\Models\Cart;
use Laravel\Mcp\Request;

trait ResolvesBasket
{
    /**
     * Find the active basket for the request's basket_token, or null.
     */
    protected function basketFromRequest(Request $request): ?Cart
    {
        return app(ResolveCart::class)->find(null, (string) $request->get('basket_token'));
    }
}
