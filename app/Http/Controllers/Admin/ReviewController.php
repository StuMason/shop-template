<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    /**
     * Review moderation: everything auto-publishes (verified buyers only),
     * this is the kill switch.
     */
    public function index(): Response
    {
        $reviews = Review::query()
            ->with('product:id,name,slug')
            ->latest()
            ->paginate(25)
            ->through(fn (Review $review): array => [
                'id' => $review->id,
                'product' => $review->product->name,
                'name' => $review->name,
                'email' => $review->email,
                'rating' => $review->rating,
                'body' => $review->body,
                'is_published' => $review->is_published,
                'date' => $review->created_at?->format('j M Y'),
            ]);

        return Inertia::render('admin/reviews/index', ['reviews' => $reviews]);
    }

    /**
     * Toggle visibility.
     */
    public function update(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate(['is_published' => ['required', 'boolean']]);

        $review->update($validated);

        return back();
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', 'Review deleted.');
    }
}
