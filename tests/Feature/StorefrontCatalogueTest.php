<?php

use App\Models\Category;
use App\Models\Product;

it('shows published products on the home page', function () {
    $published = Product::factory()->published()->withDefaultVariant()->create();
    $draft = Product::factory()->withDefaultVariant()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/home')
            ->where('latestProducts.0.name', $published->name)
            ->count('latestProducts', 1));

    expect($draft->fresh()->isPublished())->toBeFalse();
});

it('lists published products with pagination', function () {
    Product::factory()->published()->withDefaultVariant()->count(15)->create();

    $this->get(route('products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/products/index')
            ->count('products.data', 12)
            ->where('products.total', 15));
});

it('searches products by name', function () {
    Product::factory()->published()->withDefaultVariant()->create(['name' => 'Walnut Desk Organiser']);
    Product::factory()->published()->withDefaultVariant()->create(['name' => 'Steel Water Bottle']);

    $this->get(route('products.index', ['q' => 'walnut']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('products.data', 1)
            ->where('products.data.0.name', 'Walnut Desk Organiser'));
});

it('filters products by category', function () {
    $category = Category::factory()->create();
    $inCategory = Product::factory()->published()->withDefaultVariant()->create();
    $inCategory->categories()->attach($category);
    Product::factory()->published()->withDefaultVariant()->create();

    $this->get(route('products.index', ['category' => $category->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->count('products.data', 1)
            ->where('products.data.0.id', $inCategory->id));
});

it('sorts products by price', function () {
    Product::factory()->published()->withDefaultVariant(price: 5000)->create(['name' => 'Expensive']);
    Product::factory()->published()->withDefaultVariant(price: 1000)->create(['name' => 'Cheap']);

    $this->get(route('products.index', ['sort' => 'price_asc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('products.data.0.name', 'Cheap'));
});

it('shows a published product with its variants and options', function () {
    $product = Product::factory()->published()->withDefaultVariant(price: 1999)->create();

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/products/show')
            ->where('product.name', $product->name)
            ->where('product.variants.0.price', '£19.99'));
});

it('returns 404 for draft products', function () {
    $draft = Product::factory()->withDefaultVariant()->create();

    $this->get(route('products.show', $draft))->assertNotFound();
});

it('shows a category page with only its published products', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->published()->withDefaultVariant()->create();
    $product->categories()->attach($category);

    $this->get(route('categories.show', $category))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/categories/show')
            ->where('category.name', $category->name)
            ->count('products.data', 1));
});

it('returns 404 for inactive categories', function () {
    $category = Category::factory()->inactive()->create();

    $this->get(route('categories.show', $category))->assertNotFound();
});
