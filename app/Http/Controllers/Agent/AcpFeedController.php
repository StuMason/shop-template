<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ShopSettings;
use Illuminate\Http\JsonResponse;

/**
 * The product feed agents ingest for discovery. Item ids are SKUs — the
 * same ids ACP checkout sessions accept as line items.
 */
class AcpFeedController extends Controller
{
    public function __invoke(ShopSettings $settings): JsonResponse
    {
        $items = Product::query()
            ->published()
            ->with(['variants', 'categories', 'media'])
            ->get()
            ->flatMap(fn (Product $product): array => $product->variants->map(
                fn (ProductVariant $variant): array => [
                    'id' => $variant->sku,
                    'title' => $product->name.($variant->displayName() !== 'Default' ? " ({$variant->displayName()})" : ''),
                    'description' => $product->description,
                    'link' => route('products.show', $product->slug),
                    'image_link' => $product->getFirstMediaUrl('images', 'large') ?: null,
                    'price' => [
                        'amount' => $variant->price,
                        'currency' => strtolower($settings->currency()),
                    ],
                    'availability' => $variant->stock > 0 ? 'in_stock' : 'out_of_stock',
                    'inventory_quantity' => $variant->stock,
                    'product_category' => $product->categories->pluck('name')->implode(' > '),
                ],
            )->all())
            ->values();

        return response()->json([
            'shop' => [
                'name' => $settings->name(),
                'currency' => strtolower($settings->currency()),
                'checkout' => [
                    'protocol' => 'acp',
                    'sessions_url' => route('acp.sessions.store'),
                ],
            ],
            'items' => $items,
        ]);
    }
}
