<?php

namespace App\Mcp\Tools;

use App\Actions\Cart\RemoveCartItem;
use App\Http\Resources\CartResource;
use App\Mcp\Concerns\ResolvesBasket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Remove a line from a basket. Returns the updated basket.')]
class RemoveFromBasket extends Tool
{
    use ResolvesBasket;

    public function __construct(private readonly RemoveCartItem $removeCartItem) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $cart = $this->basketFromRequest($request);

        if ($cart === null) {
            return Response::error('Unknown basket_token. Call create-basket first.');
        }

        $item = $cart->items()->find((int) $request->get('item_id'));

        if ($item === null) {
            return Response::error('No basket line with that item_id in this basket.');
        }

        $this->removeCartItem->handle($cart, $item);

        return Response::json((new CartResource($cart->refresh()))->resolve());
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
            'item_id' => $schema->integer()->description('The basket line ID (from view-basket).')->required(),
        ];
    }
}
