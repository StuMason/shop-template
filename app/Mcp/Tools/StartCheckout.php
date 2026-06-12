<?php

namespace App\Mcp\Tools;

use App\Actions\Checkout\CheckoutData;
use App\Actions\Checkout\CreateOrderFromCart;
use App\Actions\Checkout\ValidateCartDiscount;
use App\Actions\Checkout\ValidateCartStock;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidDiscountException;
use App\Mcp\Concerns\ResolvesBasket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Turn a basket into an order and get a secure payment link. Payment is pay-by-bank: a HUMAN must open the returned pay_url, review the order, and authorise the payment in their own banking app. Agents cannot complete payment themselves — give the pay_url to the customer.')]
class StartCheckout extends Tool
{
    use ResolvesBasket;

    public function __construct(
        private readonly ValidateCartStock $validateCartStock,
        private readonly ValidateCartDiscount $validateCartDiscount,
        private readonly CreateOrderFromCart $createOrderFromCart,
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $cart = $this->basketFromRequest($request);

        if ($cart === null) {
            return Response::error('Unknown basket_token. Call create-basket first.');
        }

        if ($cart->items()->count() === 0) {
            return Response::error('The basket is empty. Add items with add-to-basket first.');
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'shipping_method_id' => ['required', 'integer', 'exists:shipping_methods,id'],
            ...CheckoutData::addressRules('shipping_address'),
        ]);

        if ($validator->fails()) {
            return Response::error('Invalid checkout details: '.implode(' ', $validator->errors()->all()));
        }

        $validated = $validator->validated();

        try {
            $this->validateCartStock->handle($cart);
            $this->validateCartDiscount->handle($cart, $validated['email']);

            $order = $this->createOrderFromCart->handle($cart, new CheckoutData(
                email: $validated['email'],
                shippingAddress: $validated['shipping_address'],
                shippingMethodId: (int) $validated['shipping_method_id'],
            ));
        } catch (InsufficientStockException|InvalidDiscountException $exception) {
            return Response::error($exception->getMessage());
        }

        return Response::json([
            'order_number' => $order->number,
            'total' => $order->formattedTotal(),
            'pay_url' => URL::signedRoute('checkout.pay', ['order' => $order]),
            'instructions' => 'Send the customer to pay_url. They will review the order and approve the payment in their own banking app. Check progress with get-order-status.',
        ]);
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
            'email' => $schema->string()->description('Customer email for order confirmation.')->required(),
            'shipping_method_id' => $schema->integer()->description('Delivery method ID from list-shipping-methods.')->required(),
            'shipping_address' => $schema->object([
                'name' => $schema->string()->required(),
                'line1' => $schema->string()->required(),
                'line2' => $schema->string(),
                'city' => $schema->string()->required(),
                'county' => $schema->string(),
                'postcode' => $schema->string()->required(),
                'country' => $schema->string()->description('ISO 2-letter country code, e.g. GB.')->required(),
                'phone' => $schema->string(),
            ])->description('Where to deliver the order.')->required(),
        ];
    }
}
