<?php

namespace App\Mcp\Tools;

use App\Actions\Cart\ApplyDiscount;
use App\Exceptions\InvalidDiscountException;
use App\Http\Resources\CartResource;
use App\Mcp\Concerns\ResolvesBasket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Apply a discount code to a basket. Returns the updated basket with the discount reflected in the totals.')]
class ApplyDiscountToBasket extends Tool
{
    use ResolvesBasket;

    public function __construct(private readonly ApplyDiscount $applyDiscount) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $cart = $this->basketFromRequest($request);

        if ($cart === null) {
            return Response::error('Unknown basket_token. Call create-basket first.');
        }

        try {
            $this->applyDiscount->handle($cart, (string) $request->get('code'));
        } catch (InvalidDiscountException $exception) {
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
            'code' => $schema->string()->description('The discount code to apply.')->required(),
        ];
    }
}
