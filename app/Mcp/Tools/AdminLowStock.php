<?php

namespace App\Mcp\Tools;

use App\Models\ProductVariant;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Variants at or below their low-stock threshold (including out of stock), with product names and current levels.')]
class AdminLowStock extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $variants = ProductVariant::query()
            ->with('product:id,name,slug', 'optionValues')
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->orderBy('stock')
            ->take(100)
            ->get()
            ->map(fn (ProductVariant $variant): array => [
                'product' => $variant->product->name,
                'variant' => $variant->displayName(),
                'sku' => $variant->sku,
                'stock' => $variant->stock,
                'threshold' => $variant->low_stock_threshold,
            ]);

        if ($variants->isEmpty()) {
            return Response::text('Nothing is low on stock.');
        }

        return Response::json($variants->all());
    }
}
