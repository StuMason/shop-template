<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Storefront\PageController;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ShopSettings;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Machine-readable surfaces: sitemap.xml for crawlers, llms.txt and per-product
 * markdown for AI agents. All hand-built — a shop sitemap is three loops.
 */
class SiteController extends Controller
{
    public function __construct(private readonly ShopSettings $settings) {}

    public function robots(): Response
    {
        $base = rtrim(config('app.url'), '/');

        $aiCrawlers = [
            'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'anthropic-ai',
            'Claude-Web', 'PerplexityBot', 'Google-Extended', 'CCBot',
        ];

        $out = "# This shop is AI-agent friendly. Machine-readable catalogue at /llms.txt,\n";
        $out .= "# per-product markdown at /products/{slug}.md, MCP server at /mcp/shop.\n\n";
        $out .= "User-agent: *\nAllow: /\n";

        foreach (['/admin', '/account', '/basket', '/checkout', '/settings', '/login', '/register', '/dashboard'] as $path) {
            $out .= "Disallow: {$path}\n";
        }

        foreach ($aiCrawlers as $crawler) {
            $out .= "\nUser-agent: {$crawler}\nAllow: /\n";
        }

        $out .= "\nSitemap: {$base}/sitemap.xml\n";

        return response($out, 200)->header('Content-Type', 'text/plain; charset=utf-8');
    }

    public function sitemap(): Response
    {
        $xml = Cache::remember('site.sitemap', now()->addHour(), function (): string {
            $base = rtrim(config('app.url'), '/');

            $rows = [
                '  <url><loc>'.htmlspecialchars($base).'</loc><priority>1.0</priority></url>',
                '  <url><loc>'.htmlspecialchars($base.'/products').'</loc><priority>0.8</priority></url>',
            ];

            foreach (PageController::available() as $page) {
                $rows[] = '  <url><loc>'.htmlspecialchars($base.'/pages/'.$page).'</loc><priority>0.4</priority></url>';
            }

            foreach (Category::query()->active()->orderBy('position')->get() as $category) {
                $rows[] = '  <url><loc>'.htmlspecialchars($base.'/categories/'.$category->slug).'</loc><priority>0.7</priority></url>';
            }

            foreach (Product::query()->published()->orderBy('name')->get() as $product) {
                $rows[] = '  <url><loc>'.htmlspecialchars($base.'/products/'.$product->slug).'</loc>'
                    .'<lastmod>'.$product->updated_at?->toDateString().'</lastmod>'
                    .'<priority>0.7</priority></url>';
            }

            return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
                .implode("\n", $rows)."\n"
                .'</urlset>'."\n";
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function llms(): Response
    {
        $out = Cache::remember('site.llms', now()->addHour(), function (): string {
            $base = rtrim(config('app.url'), '/');

            $out = "# {$this->settings->name()}\n\n";
            $out .= "> {$this->settings->tagline()} {$this->settings->description()}\n\n";
            $out .= "Each product has a machine-readable page at {$base}/products/SLUG.md and the full catalogue is at {$base}/llms-full.txt\n\n";
            $out .= "Agents can browse, build a basket and create a checkout link via the MCP server at {$base}/mcp/shop. ";
            $out .= "Payment is pay-by-bank: a human always reviews the order and authorises payment in their own banking app.\n\n";

            $categories = Category::query()->active()->orderBy('position')->get();

            if ($categories->isNotEmpty()) {
                $out .= "## Categories\n\n";
                foreach ($categories as $category) {
                    $out .= "- [{$category->name}]({$base}/categories/{$category->slug}): {$category->description}\n";
                }
                $out .= "\n";
            }

            $out .= "## Products\n\n";
            foreach (Product::query()->published()->with('defaultVariant')->orderBy('name')->get() as $product) {
                $price = $product->defaultVariant?->formattedPrice() ?? '';
                $out .= "- [{$product->name}]({$base}/products/{$product->slug}.md): {$price}\n";
            }

            return $out;
        });

        return response($out, 200)->header('Content-Type', 'text/markdown; charset=utf-8');
    }

    public function llmsFull(): Response
    {
        $out = Cache::remember('site.llms-full', now()->addHour(), function (): string {
            return Product::query()
                ->published()
                ->with(['options.values', 'variants.optionValues', 'categories'])
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product): string => $this->productToMarkdown($product))
                ->implode("\n\n---\n\n");
        });

        return response($out, 200)->header('Content-Type', 'text/markdown; charset=utf-8');
    }

    public function productMarkdown(Product $product): Response
    {
        abort_unless($product->isPublished(), 404);

        $product->load(['options.values', 'variants.optionValues', 'categories']);

        return response($this->productToMarkdown($product), 200)
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }

    protected function productToMarkdown(Product $product): string
    {
        $base = rtrim(config('app.url'), '/');

        $out = "# {$product->name}\n\n";

        if ($product->categories->isNotEmpty()) {
            $out .= 'Categories: '.$product->categories->pluck('name')->implode(', ')."\n";
        }

        $out .= "Canonical URL: {$base}/products/{$product->slug}\n\n";

        if ($product->description !== null) {
            $out .= trim($product->description)."\n\n";
        }

        $out .= "## Variants\n\n";
        $out .= "| SKU | Options | Price | In stock |\n";
        $out .= "| --- | --- | --- | --- |\n";

        foreach ($product->variants as $variant) {
            /** @var ProductVariant $variant */
            $out .= "| {$variant->sku} | {$variant->displayName()} | {$variant->formattedPrice()} | "
                .($variant->isInStock() ? 'Yes' : 'No')." |\n";
        }

        $out .= "\nTo purchase: use the MCP server at {$base}/mcp/shop (create-basket, add-to-basket with a variant ID, start-checkout) ";
        $out .= "or visit {$base}/products/{$product->slug}. Payment is authorised by the customer in their own banking app.\n";

        return $out;
    }
}
