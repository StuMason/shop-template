<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('q')->trim()->toString();

        $products = Product::query()
            ->with(['defaultVariant', 'media'])
            ->withCount('variants')
            ->when($search !== '', fn (Builder $query) => $query->whereLike('name', "%{$search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'status' => $product->status->value,
                'price' => $product->defaultVariant?->formattedPrice(),
                'variants_count' => $product->variants_count,
                'image' => $product->imagePayload('thumb'),
            ]);

        return Inertia::render('admin/products/index', [
            'products' => $products,
            'filters' => ['q' => $search],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('admin/products/create', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        $product = Product::create([
            ...$request->safe()->except('category_ids'),
            'published_at' => $request->string('status')->toString() === 'published' ? now() : null,
        ]);

        $product->categories()->sync($request->validated('category_ids', []));

        $product->variants()->create([
            'sku' => strtoupper('SKU-'.$product->id.'-DEFAULT'),
            'price' => 0,
            'stock' => 0,
            'is_default' => true,
        ]);

        return to_route('admin.products.edit', $product)
            ->with('success', 'Product created. Add variants, pricing and images.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): Response
    {
        $product->load(['options.values', 'variants.optionValues', 'categories', 'media']);

        return Inertia::render('admin/products/edit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'status' => $product->status->value,
                'vat_zero_rated' => $product->vat_zero_rated,
                'is_digital' => $product->is_digital,
                'download_file' => $product->getFirstMedia('downloads')?->file_name,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
                'category_ids' => $product->categories->pluck('id')->all(),
                'options' => $product->options->map(fn (ProductOption $option): array => [
                    'id' => $option->id,
                    'name' => $option->name,
                    'values' => $option->values->map(fn (ProductOptionValue $value): array => [
                        'id' => $value->id,
                        'value' => $value->value,
                    ])->all(),
                ])->all(),
                'variants' => $product->variants->map(fn (ProductVariant $variant): array => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'printful_variant_id' => $variant->printful_variant_id,
                    'price' => $variant->price,
                    'compare_at_price' => $variant->compare_at_price,
                    'stock' => $variant->stock,
                    'low_stock_threshold' => $variant->low_stock_threshold,
                    'is_default' => $variant->is_default,
                    'option_value_ids' => $variant->optionValues->pluck('id')->all(),
                    'display_name' => $variant->displayName(),
                ])->all(),
                'images' => $product->getMedia('images')->map(fn ($media): array => [
                    'id' => $media->id,
                    'url' => $media->getUrl('thumb'),
                    'name' => $media->file_name,
                ])->values()->all(),
            ],
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $publishedAt = match (true) {
            $request->string('status')->toString() !== 'published' => null,
            $product->published_at !== null => $product->published_at,
            default => now(),
        };

        $product->update([
            ...$request->safe()->except('category_ids'),
            'published_at' => $publishedAt,
        ]);

        $product->categories()->sync($request->validated('category_ids', []));

        return back()->with('success', 'Product updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return to_route('admin.products.index')->with('success', 'Product deleted.');
    }
}
