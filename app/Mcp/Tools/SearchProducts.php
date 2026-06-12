<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Search the shop catalogue. Returns matching products with their slugs, price and stock availability. Use get-product for full details and variants.')]
class SearchProducts extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $query = (string) $request->get('query', '');

        $products = ($query === ''
            ? Product::query()->published()
            : Product::query()->published()->whereIn('id', Product::search($query)->keys()))
            ->with(['defaultVariant', 'variants'])
            ->latest('published_at')
            ->take(20)
            ->get();

        if ($products->isEmpty()) {
            return Response::text('No products found. Try a broader query or call search-products without a query to list the latest products.');
        }

        return Response::json($products->map(fn (Product $product): array => [
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->defaultVariant?->formattedPrice(),
            'in_stock' => $product->variants->contains(fn ($variant): bool => $variant->isInStock()),
            'url' => route('products.show', $product),
        ])->all());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Search terms. Omit or leave empty to list the latest products.'),
        ];
    }
}
