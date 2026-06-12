<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Cart\AddToCart;
use App\Actions\Cart\ApplyDiscount;
use App\Actions\Cart\RemoveCartItem;
use App\Actions\Cart\RemoveDiscount;
use App\Actions\Cart\ResolveCart;
use App\Actions\Cart\UpdateCartItem;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidDiscountException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private readonly ResolveCart $resolveCart) {}

    /**
     * The basket page. The basket payload itself is a shared Inertia prop.
     */
    public function show(): Response
    {
        return Inertia::render('storefront/basket');
    }

    /**
     * Add a variant to the basket.
     */
    public function store(Request $request, AddToCart $addToCart): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')],
            'quantity' => ['integer', 'min:1', 'max:99'],
        ]);

        $cart = $this->resolveCartForWrite($request);

        $variant = ProductVariant::query()->whereKey($validated['variant_id'])->firstOrFail();

        try {
            $addToCart->handle($cart, $variant, $validated['quantity'] ?? 1);
        } catch (InsufficientStockException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        return back()->with('success', "{$variant->product->name} added to basket.");
    }

    /**
     * Change a line's quantity.
     */
    public function update(Request $request, CartItem $item, UpdateCartItem $updateCartItem): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $cart = $this->resolveCartForWrite($request);

        try {
            $updateCartItem->handle($cart, $item, $validated['quantity']);
        } catch (InsufficientStockException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        return back();
    }

    /**
     * Remove a line from the basket.
     */
    public function destroy(Request $request, CartItem $item, RemoveCartItem $removeCartItem): RedirectResponse
    {
        $removeCartItem->handle($this->resolveCartForWrite($request), $item);

        return back();
    }

    /**
     * Apply a discount code to the basket.
     */
    public function applyDiscount(Request $request, ApplyDiscount $applyDiscount): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        try {
            $discount = $applyDiscount->handle($this->resolveCartForWrite($request), $validated['code']);
        } catch (InvalidDiscountException $exception) {
            throw ValidationException::withMessages(['code' => $exception->getMessage()]);
        }

        return back()->with('success', "Code {$discount->code} applied.");
    }

    /**
     * Remove the basket's discount code.
     */
    public function removeDiscount(Request $request, RemoveDiscount $removeDiscount): RedirectResponse
    {
        $removeDiscount->handle($this->resolveCartForWrite($request));

        return back();
    }

    /**
     * Resolve the request's cart, remembering the token for guests.
     */
    protected function resolveCartForWrite(Request $request): Cart
    {
        $cart = $this->resolveCart->handle($request->user(), $request->session()->get('cart_token'));

        if ($cart->user_id === null) {
            $request->session()->put('cart_token', $cart->token);
        }

        return $cart;
    }
}
