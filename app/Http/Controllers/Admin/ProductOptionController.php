<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductOptionRequest;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ProductOptionController extends Controller
{
    /**
     * Store a new option with its values on the product.
     */
    public function store(ProductOptionRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product): void {
            /** @var ProductOption $option */
            $option = $product->options()->create(['name' => $request->validated('name')]);

            foreach ($request->validated('values') as $position => $value) {
                $option->values()->create(['value' => $value, 'position' => $position]);
            }
        });

        return back()->with('success', 'Option added.');
    }

    /**
     * Update an option: rename it and sync its values by text. Values still
     * attached to variants are kept even if removed from the list.
     */
    public function update(ProductOptionRequest $request, Product $product, ProductOption $option): RedirectResponse
    {
        abort_unless($option->product_id === $product->id, 404);

        DB::transaction(function () use ($request, $option): void {
            $option->update(['name' => $request->validated('name')]);

            $values = $request->validated('values');

            $option->values()
                ->whereNotIn('value', $values)
                ->whereDoesntHave('variants')
                ->delete();

            foreach ($values as $position => $value) {
                $option->values()->updateOrCreate(['value' => $value], ['position' => $position]);
            }
        });

        return back()->with('success', 'Option updated.');
    }

    /**
     * Remove an option and its values (detaching them from variants).
     */
    public function destroy(Product $product, ProductOption $option): RedirectResponse
    {
        abort_unless($option->product_id === $product->id, 404);

        $option->delete();

        return back()->with('success', 'Option removed.');
    }
}
