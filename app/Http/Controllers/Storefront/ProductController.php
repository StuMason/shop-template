<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Http\Resources\ProductDetailResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Product listing with search, category filter, sorting and pagination.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('q')->trim()->toString();
        $categorySlug = $request->string('category')->toString();
        $sort = $request->string('sort', 'newest')->toString();

        $query = Product::query()
            ->published()
            ->with(['defaultVariant', 'variants', 'media'])
            ->when($search !== '', fn (Builder $query) => $query->whereIn(
                'id',
                Product::search($search)->keys(),
            ))
            ->when($categorySlug !== '', fn (Builder $query) => $query->whereHas(
                'categories',
                fn (Builder $query) => $query->where('slug', $categorySlug),
            ));

        match ($sort) {
            'price_asc', 'price_desc' => $query
                ->addSelect([
                    'min_price' => ProductVariant::query()
                        ->selectRaw('min(price)')
                        ->whereColumn('product_id', 'products.id'),
                ])
                ->orderBy('min_price', $sort === 'price_asc' ? 'asc' : 'desc'),
            default => $query->latest('published_at'),
        };

        $products = $query->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product): array => (new ProductCardResource($product))->resolve());

        return Inertia::render('storefront/products/index', [
            'products' => $products,
            'categories' => Category::query()->active()->orderBy('position')->get(['id', 'name', 'slug']),
            'filters' => [
                'q' => $search,
                'category' => $categorySlug,
                'sort' => $sort,
            ],
        ]);
    }

    /**
     * Product detail page with deferred related products.
     */
    public function show(Product $product): Response
    {
        abort_unless($product->isPublished(), 404);

        $product->load(['options.values', 'variants.optionValues', 'categories', 'media']);

        $reviews = $product->reviews()
            ->where('is_published', true)
            ->latest()
            ->get();

        return Inertia::render('storefront/products/show', [
            'product' => (new ProductDetailResource($product))->resolve(),
            'reviews' => [
                'count' => $reviews->count(),
                'average' => $reviews->isNotEmpty() ? round($reviews->avg('rating'), 1) : null,
                'items' => $reviews->take(10)->map(fn ($review): array => [
                    'id' => $review->id,
                    'name' => $review->name,
                    'rating' => $review->rating,
                    'body' => $review->body,
                    'date' => $review->created_at?->format('j M Y'),
                ])->values(),
            ],
            'relatedProducts' => Inertia::defer(function () use ($product): array {
                $related = Product::query()
                    ->published()
                    ->whereNot('id', $product->id)
                    ->whereHas('categories', fn (Builder $query) => $query->whereIn(
                        'categories.id',
                        $product->categories->pluck('id'),
                    ))
                    ->with(['defaultVariant', 'variants', 'media'])
                    ->inRandomOrder()
                    ->take(4)
                    ->get();

                return ProductCardResource::collection($related)->resolve();
            }),
        ]);
    }
}
