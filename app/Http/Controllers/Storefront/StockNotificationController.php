<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\StockNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StockNotificationController extends Controller
{
    /**
     * "Tell me when it's back" — open to guests; idempotent per email+variant.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
        ]);

        $variant = ProductVariant::query()->whereKey($validated['variant_id'])->firstOrFail();

        if ($variant->stock > 0) {
            return back()->with('success', "Good news — it's in stock right now.");
        }

        StockNotification::query()->firstOrCreate([
            'email' => strtolower($validated['email']),
            'product_variant_id' => $variant->id,
        ]);

        return back()->with('success', "We'll email you the moment it's back.");
    }
}
