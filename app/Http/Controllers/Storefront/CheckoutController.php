<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Cart\CalculateCartTotals;
use App\Actions\Cart\ResolveCart;
use App\Actions\Checkout\CreateOrderFromCart;
use App\Actions\Checkout\ValidateCartStock;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\StoreCheckoutRequest;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(private readonly ResolveCart $resolveCart) {}

    /**
     * The checkout form: contact, addresses, shipping method.
     */
    public function show(Request $request, CalculateCartTotals $totals): Response|RedirectResponse
    {
        $cart = $this->resolveCart->find($request->user(), $request->session()->get('cart_token'));

        if ($cart === null || $cart->items()->count() === 0) {
            return to_route('cart.show');
        }

        $cartTotals = $totals->handle($cart);

        $shippingMethods = ShippingMethod::query()
            ->active()
            ->whereHas('zone', fn ($query) => $query->where('is_active', true))
            ->orderBy('position')
            ->get()
            ->map(fn (ShippingMethod $method): array => [
                'id' => $method->id,
                'name' => $method->name,
                'description' => $method->description,
                'price' => $method->formattedPriceFor($cartTotals->subtotal),
            ]);

        $defaultAddress = $request->user()?->addresses()
            ->orderByDesc('is_default_shipping')
            ->first();

        return Inertia::render('checkout/show', [
            'shippingMethods' => $shippingMethods,
            'email' => $request->user()?->email,
            'defaultAddress' => $defaultAddress?->toSnapshot(),
            'countries' => array_keys(ShippingZone::query()->active()->get()
                ->flatMap(fn (ShippingZone $zone): array => array_fill_keys($zone->countries, true))
                ->all()),
        ]);
    }

    /**
     * Create the pending order and hand off to the pay page.
     */
    public function store(
        StoreCheckoutRequest $request,
        ValidateCartStock $validateCartStock,
        CreateOrderFromCart $createOrderFromCart,
    ): RedirectResponse {
        $cart = $this->resolveCart->find($request->user(), $request->session()->get('cart_token'));

        if ($cart === null || $cart->items()->count() === 0) {
            return to_route('cart.show');
        }

        try {
            $validateCartStock->handle($cart);
            $order = $createOrderFromCart->handle($cart, $request->toCheckoutData());
        } catch (InsufficientStockException $exception) {
            throw ValidationException::withMessages(['basket' => $exception->getMessage()]);
        }

        return redirect()->to(URL::signedRoute('checkout.pay', ['order' => $order]));
    }
}
