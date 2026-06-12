<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Symfony\Component\HttpFoundation\Response;

class DownloadController extends Controller
{
    /**
     * How many times a single line's files may be fetched. Generous —
     * the limit exists to stop links becoming public mirrors.
     */
    public const DOWNLOAD_LIMIT = 25;

    /**
     * Serve a digital order item's files. The temporary signed URL is the
     * capability (it lives in the buyer's email); the order must be paid.
     */
    public function __invoke(Order $order, OrderItem $item): Response
    {
        abort_unless($item->order_id === $order->id && $item->is_digital, 404);

        abort_unless(in_array($order->status, [
            OrderStatus::Paid,
            OrderStatus::Processing,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
        ], true), 403, 'This order is not paid.');

        abort_if(
            $item->download_count >= self::DOWNLOAD_LIMIT,
            429,
            'Download limit reached — contact us for fresh links.',
        );

        // The variant may have been deleted since the order was placed;
        // fall back to the snapshot name.
        $product = $item->product_variant_id !== null
            ? $item->variant->product
            : Product::query()->firstWhere('name', $item->product_name);

        $media = $product?->getFirstMedia('downloads');

        abort_if($media === null, 404, 'No file is attached to this product yet — contact us.');

        $item->increment('download_count');

        return response()->download($media->getPath(), $media->file_name);
    }
}
