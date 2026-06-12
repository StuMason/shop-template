<?php

namespace App\Mcp\Tools;

use App\Http\Resources\ProductDetailResource;
use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get a product\'s full details by slug: description, options, and every variant with its SKU, price and stock. Variant IDs are what you add to a basket.')]
class GetProduct extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $product = Product::query()
            ->published()
            ->firstWhere('slug', (string) $request->get('slug'));

        if ($product === null) {
            return Response::error('No published product with that slug. Use search-products to find slugs.');
        }

        $product->load(['options.values', 'variants.optionValues', 'categories', 'media']);

        return Response::json((new ProductDetailResource($product))->resolve());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()
                ->description('The product slug, e.g. "enamel-mug".')
                ->required(),
        ];
    }
}
