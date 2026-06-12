<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCardResource;
use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): Response
    {
        $latest = Product::query()
            ->published()
            ->with(['defaultVariant', 'variants', 'media'])
            ->latest('published_at')
            ->take(8)
            ->get();

        $categories = Category::query()
            ->active()
            ->whereNull('parent_id')
            ->orderBy('position')
            ->get(['id', 'name', 'slug', 'description']);

        return Inertia::render('storefront/home', [
            'latestProducts' => ProductCardResource::collection($latest)->resolve(),
            'categories' => $categories,
        ]);
    }
}
