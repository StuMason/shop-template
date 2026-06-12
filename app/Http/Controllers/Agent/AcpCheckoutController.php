<?php

namespace App\Http\Controllers\Agent;

use App\Actions\Cart\AddToCart;
use App\Actions\Cart\ApplyDiscount;
use App\Actions\Cart\RemoveDiscount;
use App\Actions\Checkout\CheckoutData;
use App\Actions\Checkout\CreateOrderFromCart;
use App\Actions\Checkout\QuoteCart;
use App\Actions\Checkout\ValidateCartDiscount;
use App\Actions\Checkout\ValidateCartStock;
use App\Enums\CartStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidDiscountException;
use App\Http\Controllers\Controller;
use App\Models\AgentCheckoutSession;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Agentic Commerce Protocol checkout sessions: the standard surface AI
 * shopping agents (ChatGPT et al.) speak. Thin adapter over the same cart
 * and checkout actions as the web and MCP surfaces — see docs/architecture.
 */
class AcpCheckoutController extends Controller
{
    public function __construct(
        private readonly AddToCart $addToCart,
        private readonly ApplyDiscount $applyDiscount,
        private readonly RemoveDiscount $removeDiscount,
        private readonly QuoteCart $quoteCart,
    ) {}

    /**
     * POST /acp/checkout_sessions — create a session from line items.
     */
    public function store(Request $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey !== null) {
            $existing = AgentCheckoutSession::query()
                ->firstWhere('idempotency_key', $idempotencyKey);

            if ($existing !== null) {
                return $this->payload($existing);
            }
        }

        $validated = $this->validateSessionInput($request, itemsRequired: true);

        $cart = Cart::create([
            'token' => (string) Str::ulid(),
            'status' => CartStatus::Active,
        ]);

        $session = new AgentCheckoutSession([
            'cart_id' => $cart->id,
            'idempotency_key' => $idempotencyKey,
        ]);

        return $this->applyAndRespond($session, $cart, $validated, created: true);
    }

    /**
     * GET /acp/checkout_sessions/{session}
     */
    public function show(AgentCheckoutSession $session): JsonResponse
    {
        return $this->payload($session);
    }

    /**
     * POST /acp/checkout_sessions/{session} — update buyer, items, fulfillment.
     */
    public function update(Request $request, AgentCheckoutSession $session): JsonResponse
    {
        if (! $session->isOpen()) {
            return $this->payload($session, ['Session is no longer open.'], 409);
        }

        $validated = $this->validateSessionInput($request, itemsRequired: false);

        return $this->applyAndRespond($session, $session->cart, $validated);
    }

    /**
     * POST /acp/checkout_sessions/{session}/complete — place the order.
     */
    public function complete(
        AgentCheckoutSession $session,
        ValidateCartStock $validateCartStock,
        ValidateCartDiscount $validateCartDiscount,
        CreateOrderFromCart $createOrderFromCart,
    ): JsonResponse {
        if ($session->order_id !== null) {
            return $this->payload($session->refresh());
        }

        if (! $session->isOpen()) {
            return $this->payload($session, ['Session is no longer open.'], 409);
        }

        $fullyDigital = $session->cart->isFullyDigital();

        $missing = array_keys(array_filter([
            'buyer email' => $session->email === null,
            'shipping address' => $session->shipping_address === null,
            'fulfillment option' => ! $fullyDigital && $session->shipping_method_id === null,
        ]));

        if ($missing !== []) {
            return $this->payload($session, ['Missing before completion: '.implode(', ', $missing).'.'], 422);
        }

        $cart = $session->cart;

        try {
            $validateCartStock->handle($cart);
            $validateCartDiscount->handle($cart, (string) $session->email);

            $order = $createOrderFromCart->handle($cart, new CheckoutData(
                email: (string) $session->email,
                shippingAddress: $session->shipping_address ?? [],
                shippingMethodId: $session->shipping_method_id !== null
                    ? (int) $session->shipping_method_id
                    : null,
                customerNote: 'Placed by an AI agent via ACP.',
            ));
        } catch (InsufficientStockException|InvalidDiscountException $exception) {
            return $this->payload($session, [$exception->getMessage()], 422);
        }

        $session->update(['status' => 'completed', 'order_id' => $order->id]);

        return $this->payload($session->refresh());
    }

    /**
     * POST /acp/checkout_sessions/{session}/cancel
     */
    public function cancel(AgentCheckoutSession $session): JsonResponse
    {
        if ($session->status === 'completed') {
            return $this->payload($session, ['Completed sessions cannot be canceled.'], 409);
        }

        if ($session->isOpen()) {
            $session->update(['status' => 'canceled']);
            $session->cart->update(['status' => CartStatus::Abandoned]);
        }

        return $this->payload($session->refresh());
    }

    /**
     * Validate the create/update body.
     *
     * @return array<string, mixed>
     */
    protected function validateSessionInput(Request $request, bool $itemsRequired): array
    {
        // Address is optional until completion; when present, full rules apply.
        $addressRules = collect(CheckoutData::addressRules('shipping_address'))
            ->map(fn (array $rules): array => array_map(
                fn (string $rule): string => $rule === 'required' ? 'required_with:shipping_address' : $rule,
                $rules,
            ))
            ->all();
        $addressRules['shipping_address'] = ['sometimes', 'array'];

        return $request->validate([
            'items' => [$itemsRequired ? 'required' : 'sometimes', 'array', 'min:1'],
            'items.*.id' => ['required', 'string', 'max:64'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'buyer' => ['sometimes', 'array'],
            'buyer.email' => ['sometimes', 'email', 'max:255'],
            'fulfillment_option_id' => ['sometimes', 'integer'],
            'discount_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            ...$addressRules,
        ]);
    }

    /**
     * Apply validated input to the session + cart, then respond with state.
     *
     * @param  array<string, mixed>  $validated
     */
    protected function applyAndRespond(
        AgentCheckoutSession $session,
        Cart $cart,
        array $validated,
        bool $created = false,
    ): JsonResponse {
        $messages = [];

        if (isset($validated['items'])) {
            $cart->items()->delete();

            foreach ($validated['items'] as $item) {
                $variant = ProductVariant::query()->firstWhere('sku', $item['id']);

                if ($variant === null) {
                    $messages[] = "Unknown item id {$item['id']} — ids come from the product feed (SKUs).";

                    continue;
                }

                try {
                    $this->addToCart->handle($cart, $variant, (int) $item['quantity']);
                } catch (InsufficientStockException $exception) {
                    $messages[] = $exception->getMessage();
                }
            }
        }

        if (isset($validated['buyer']['email'])) {
            $session->email = $validated['buyer']['email'];
        }

        if (isset($validated['shipping_address'])) {
            $session->shipping_address = $validated['shipping_address'];
        }

        if (isset($validated['fulfillment_option_id'])) {
            $method = ShippingMethod::query()->active()->whereKey($validated['fulfillment_option_id'])->first();

            if ($method === null) {
                $messages[] = 'Unknown fulfillment_option_id.';
            } else {
                $session->shipping_method_id = $method->id;
            }
        }

        if (array_key_exists('discount_code', $validated)) {
            $code = $validated['discount_code'];

            try {
                if ($code === null || $code === '') {
                    $this->removeDiscount->handle($cart);
                } else {
                    $this->applyDiscount->handle($cart, $code);
                }
            } catch (InvalidDiscountException $exception) {
                $messages[] = $exception->getMessage();
            }
        }

        $session->save();

        return $this->payload($session, $messages, $created ? 201 : 200);
    }

    /**
     * The full ACP session state: line items, totals, fulfillment options.
     *
     * @param  list<string>  $messages
     */
    protected function payload(AgentCheckoutSession $session, array $messages = [], int $status = 200): JsonResponse
    {
        $cart = $session->cart->load('items.variant.product', 'discount');
        $method = $session->shippingMethod;
        $quote = $this->quoteCart->handle($cart, $method);

        $fulfillmentOptions = ShippingMethod::query()
            ->active()
            ->whereHas('zone', fn ($query) => $query->where('is_active', true))
            ->orderBy('position')
            ->get()
            ->map(fn (ShippingMethod $option): array => [
                'id' => $option->id,
                'label' => $option->name,
                'description' => $option->description,
                'amount' => $option->priceFor($quote['subtotal'] - $quote['discount']),
            ]);

        $links = [];

        if ($session->order !== null) {
            // A human authorises pay-by-bank at this signed URL...
            $links['payment_url'] = URL::signedRoute('checkout.pay', ['order' => $session->order]);

            // ...or, when the x402 rail is on, the agent settles in USDC.
            if (app(PaymentManager::class)->x402Enabled()) {
                $links['x402_payment_url'] = URL::signedRoute('agent.pay.x402', ['order' => $session->order]);
            }
        }

        return response()->json([
            'id' => $session->id,
            'status' => $session->status,
            'currency' => strtolower($quote['currency']),
            'line_items' => $cart->items->map(fn (CartItem $item): array => [
                'id' => $item->variant->sku,
                'title' => $item->variant->product->name,
                'variant' => $item->variant->displayName(),
                'quantity' => $item->quantity,
                'unit_amount' => $item->variant->price,
                'total_amount' => $item->lineTotal(),
            ])->values(),
            'totals' => [
                'subtotal' => $quote['subtotal'],
                'discount' => $quote['discount'],
                'discount_code' => $quote['discount_code'],
                'fulfillment' => $quote['shipping'],
                'tax' => $quote['vat'],
                'tax_note' => $quote['vat'] > 0 ? 'VAT, included in the total' : null,
                'total' => $quote['total'],
            ],
            'buyer' => ['email' => $session->email],
            'shipping_address' => $session->shipping_address,
            'fulfillment' => [
                'selected_id' => $session->shipping_method_id,
                'options' => $fulfillmentOptions,
            ],
            'order' => $session->order !== null ? [
                'id' => $session->order->number,
                'status' => $session->order->status->value,
            ] : null,
            'links' => $links,
            'messages' => $messages,
        ], $status);
    }
}
