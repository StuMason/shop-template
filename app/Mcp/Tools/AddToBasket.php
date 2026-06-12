<?php

namespace App\Mcp\Tools;

use App\Actions\Cart\AddToCart;
use App\Exceptions\InsufficientStockException;
use App\Http\Resources\CartResource;
use App\Mcp\Concerns\ResolvesBasket;
use App\Models\ProductVariant;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a product variant to a basket. Get variant IDs from get-product. Returns the updated basket.')]
class AddToBasket extends Tool
{
    use ResolvesBasket;

    public function __construct(private readonly AddToCart $addToCart) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $cart = $this->basketFromRequest($request);

        if ($cart === null) {
            return Response::error('Unknown basket_token. Call create-basket first.');
        }

        $variant = ProductVariant::query()->find((int) $request->get('variant_id'));

        if ($variant === null) {
            return Response::error('Unknown variant_id. Use get-product to list variants.');
        }

        try {
            $this->addToCart->handle($cart, $variant, max(1, (int) $request->get('quantity', 1)));
        } catch (InsufficientStockException $exception) {
            return Response::error($exception->getMessage());
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
            'variant_id' => $schema->integer()->description('The product variant ID to add.')->required(),
            'quantity' => $schema->integer()->description('Quantity to add (default 1).'),
        ];
    }
}
