<?php

namespace App\Mcp\Tools;

use App\Actions\Cart\UpdateCartItem;
use App\Exceptions\InsufficientStockException;
use App\Http\Resources\CartResource;
use App\Mcp\Concerns\ResolvesBasket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Description('Change the quantity of a basket line. A quantity of 0 removes it. Returns the updated basket.')]
class UpdateBasketItem extends Tool
{
    use ResolvesBasket;

    public function __construct(private readonly UpdateCartItem $updateCartItem) {}

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

        try {
            $this->updateCartItem->handle($cart, $item, (int) $request->get('quantity'));
        } catch (InsufficientStockException $exception) {
            return Response::error($exception->getMessage());
        } catch (HttpException) {
            return Response::error('That line does not belong to this basket.');
        }

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
            'quantity' => $schema->integer()->description('New quantity; 0 removes the line.')->required(),
        ];
    }
}
