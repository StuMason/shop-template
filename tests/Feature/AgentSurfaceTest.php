<?php

use App\Models\Category;
use App\Models\Product;

it('serves an AI-friendly robots.txt with the sitemap', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
        ->assertSee('ClaudeBot')
        ->assertSee('GPTBot')
        ->assertSee('Disallow: /admin')
        ->assertSee('Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml');
});

it('serves a sitemap with published products and active categories only', function () {
    $published = Product::factory()->published()->withDefaultVariant()->create();
    $draft = Product::factory()->withDefaultVariant()->create();
    $category = Category::factory()->create();
    $hidden = Category::factory()->inactive()->create();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee("/products/{$published->slug}")
        ->assertDontSee("/products/{$draft->slug}")
        ->assertSee("/categories/{$category->slug}")
        ->assertDontSee("/categories/{$hidden->slug}");
});

it('serves llms.txt with the catalogue index and MCP pointer', function () {
    $product = Product::factory()->published()->withDefaultVariant(price: 1999)->create();

    $this->get('/llms.txt')
        ->assertOk()
        ->assertSee("[{$product->name}]")
        ->assertSee("/products/{$product->slug}.md")
        ->assertSee('/mcp/shop')
        ->assertSee('£19.99');
});

it('serves per-product markdown with a variants table', function () {
    $product = Product::factory()->published()->withDefaultVariant(price: 2500)->create();
    $sku = $product->variants->first()->sku;

    $this->get("/products/{$product->slug}.md")
        ->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee("# {$product->name}")
        ->assertSee($sku)
        ->assertSee('£25.00');
});

it('does not expose draft products as markdown', function () {
    $draft = Product::factory()->withDefaultVariant()->create();

    $this->get("/products/{$draft->slug}.md")->assertNotFound();
});

it('serves the full corpus at llms-full.txt', function () {
    Product::factory()->published()->withDefaultVariant()->count(2)->create();

    $response = $this->get('/llms-full.txt')->assertOk();

    expect(substr_count($response->getContent(), "\n---\n"))->toBe(1);
});
