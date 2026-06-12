<?php

namespace App\Mcp\Tools;

use App\Actions\Cart\ResolveCart;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new basket. Returns a basket_token — keep it and pass it to every basket and checkout tool. Treat it as a secret.')]
class CreateBasket extends Tool
{
    public function __construct(private readonly ResolveCart $resolveCart) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $cart = $this->resolveCart->handle(null, null);

        return Response::json([
            'basket_token' => $cart->token,
            'expires_at' => $cart->expires_at?->toIso8601String(),
        ]);
    }
}
