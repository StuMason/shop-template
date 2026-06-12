<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductMediaController extends Controller
{
    /**
     * Attach uploaded images to the product.
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:8192'],
        ]);

        foreach ($request->file('images', []) as $file) {
            $product->addMedia($file)->toMediaCollection('images');
        }

        return back()->with('success', 'Images uploaded.');
    }

    /**
     * Remove an image from the product.
     */
    public function destroy(Product $product, int $mediaId): RedirectResponse
    {
        $media = $product->getMedia('images')->firstWhere('id', $mediaId);

        abort_if($media === null, 404);

        $media->delete();

        return back()->with('success', 'Image removed.');
    }
}
