<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Category landing page with its products.
     */
    public function show(Category $category): Response
    {
        abort_unless($category->is_active, 404);

        $products = $category->products()
            ->published()
            ->with(['defaultVariant', 'variants', 'media'])
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product): array => (new ProductCardResource($product))->resolve());

        return Inertia::render('storefront/categories/show', [
            'category' => [
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'meta_title' => $category->meta_title,
                'meta_description' => $category->meta_description,
            ],
            'children' => $category->children()->active()->get(['id', 'name', 'slug']),
            'products' => $products,
        ]);
    }
}
