<?php

namespace App\Mcp\Tools;

use App\Http\Resources\CartResource;
use App\Mcp\Concerns\ResolvesBasket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('View a basket: its lines, quantities and subtotal.')]
class ViewBasket extends Tool
{
    use ResolvesBasket;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $cart = $this->basketFromRequest($request);

        if ($cart === null) {
            return Response::error('Unknown basket_token. Call create-basket first.');
        }

        return Response::json((new CartResource($cart))->resolve());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'basket_token' => $schema->string()->description('The basket token from create-basket.')->required(),
        ];
    }
}
