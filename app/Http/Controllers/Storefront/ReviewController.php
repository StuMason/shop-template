<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Verified-buyer reviews. The signed URL in the review-request email is the
 * proof of purchase — both routes verify the product was in the order.
 */
class ReviewController extends Controller
{
    /**
     * The review form (signed link from the post-delivery email).
     */
    public function create(Order $order, Product $product): Response
    {
        $this->ensurePurchased($order, $product);

        $existing = Review::query()
            ->where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->first();

        return Inertia::render('storefront/reviews/create', [
            'submit_url' => URL::signedRoute('reviews.store', ['order' => $order, 'product' => $product]),
            'order_number' => $order->number,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
            ],
            'existing' => $existing !== null ? [
                'rating' => $existing->rating,
                'body' => $existing->body,
            ] : null,
        ]);
    }

    /**
     * Store (or revise) the review.
     */
    public function store(Request $request, Order $order, Product $product): RedirectResponse
    {
        $this->ensurePurchased($order, $product);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
            'name' => ['required', 'string', 'max:64'],
        ]);

        Review::query()->updateOrCreate(
            ['order_id' => $order->id, 'product_id' => $product->id],
            [
                'email' => $order->email,
                'name' => $validated['name'],
                'rating' => (int) $validated['rating'],
                'body' => $validated['body'] ?? null,
                'is_published' => true,
            ],
        );

        return to_route('products.show', $product->slug)
            ->with('success', 'Thanks — your review is live.');
    }

    protected function ensurePurchased(Order $order, Product $product): void
    {
        $purchased = $order->items->contains(
            fn (OrderItem $item): bool => $item->variant?->product_id === $product->id
                || $item->product_name === $product->name,
        );

        abort_unless($purchased && $order->paid_at !== null, 403);
    }
}
