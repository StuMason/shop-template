<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DiscountController extends Controller
{
    /**
     * Discount code management.
     */
    public function index(): Response
    {
        $discounts = Discount::query()
            ->latest()
            ->get()
            ->map(fn (Discount $discount): array => [
                'id' => $discount->id,
                'code' => $discount->code,
                'type' => $discount->type->value,
                'value' => $discount->value,
                'min_subtotal' => $discount->min_subtotal,
                'starts_at' => $discount->starts_at?->toDateString(),
                'ends_at' => $discount->ends_at?->toDateString(),
                'max_uses' => $discount->max_uses,
                'once_per_customer' => $discount->once_per_customer,
                'used_count' => $discount->used_count,
                'is_active' => $discount->is_active,
            ]);

        return Inertia::render('admin/discounts/index', [
            'discounts' => $discounts,
        ]);
    }

    /**
     * Create a discount code.
     */
    public function store(Request $request): RedirectResponse
    {
        Discount::create($this->validated($request));

        return back()->with('success', 'Discount created.');
    }

    /**
     * Update a discount code.
     */
    public function update(Request $request, Discount $discount): RedirectResponse
    {
        $discount->update($this->validated($request, $discount));

        return back()->with('success', 'Discount updated.');
    }

    /**
     * Delete a discount code (existing orders keep their snapshot).
     */
    public function destroy(Discount $discount): RedirectResponse
    {
        $discount->delete();

        return back()->with('success', 'Discount deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Discount $discount = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', 'alpha_num:ascii', Rule::unique('discounts', 'code')->ignore($discount)],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'integer', 'min:1', 'max_digits:9'],
            'min_subtotal' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'once_per_customer' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $validated['code'] = strtoupper($validated['code']);

        if ($validated['type'] === 'percent' && $validated['value'] > 100) {
            $validated['value'] = 100;
        }

        return $validated;
    }
}
