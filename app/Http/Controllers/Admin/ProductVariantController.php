<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ProductVariantController extends Controller
{
    /**
     * Store a newly created variant on the product.
     */
    public function store(ProductVariantRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product): void {
            /** @var ProductVariant $variant */
            $variant = $product->variants()->create($request->safe()->except('option_value_ids'));

            $variant->optionValues()->sync($request->validated('option_value_ids', []));

            $this->ensureSingleDefault($product, $variant);
        });

        return back()->with('success', 'Variant added.');
    }

    /**
     * Update the specified variant.
     */
    public function update(ProductVariantRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        DB::transaction(function () use ($request, $product, $variant): void {
            $variant->update($request->safe()->except('option_value_ids'));

            $variant->optionValues()->sync($request->validated('option_value_ids', []));

            $this->ensureSingleDefault($product, $variant);
        });

        return back()->with('success', 'Variant updated.');
    }

    /**
     * Remove the specified variant. The last variant cannot be removed.
     */
    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        if ($product->variants()->count() <= 1) {
            return back()->with('error', 'A product must keep at least one variant.');
        }

        $variant->delete();

        if ($variant->is_default) {
            $product->variants()->orderBy('position')->first()?->update(['is_default' => true]);
        }

        return back()->with('success', 'Variant removed.');
    }

    /**
     * Keep exactly one default variant per product.
     */
    protected function ensureSingleDefault(Product $product, ProductVariant $current): void
    {
        if ($current->is_default) {
            $product->variants()->whereKeyNot($current->id)->update(['is_default' => false]);

            return;
        }

        if (! $product->variants()->where('is_default', true)->exists()) {
            $current->update(['is_default' => true]);
        }
    }
}
