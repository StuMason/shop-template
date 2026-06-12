<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesSeeder::class);

    $this->staff = User::factory()->create();
    $this->staff->assignRole('staff');
});

it('blocks customers from admin product management', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)->get(route('admin.products.index'))->assertForbidden();
    $this->actingAs($customer)->post(route('admin.products.store'), [])->assertForbidden();
});

it('creates a product with a default variant and a generated slug', function () {
    $this->actingAs($this->staff)
        ->post(route('admin.products.store'), [
            'name' => 'Aero Press Filter',
            'description' => 'Paper filters.',
            'status' => 'draft',
        ])
        ->assertRedirect();

    $product = Product::query()->firstWhere('slug', 'aero-press-filter');

    expect($product)->not->toBeNull()
        ->and($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->is_default)->toBeTrue();
});

it('publishes a product and sets published_at', function () {
    $product = Product::factory()->withDefaultVariant()->create();

    $this->actingAs($this->staff)
        ->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'slug' => $product->slug,
            'status' => 'published',
        ])
        ->assertRedirect();

    expect($product->fresh()->isPublished())->toBeTrue();
});

it('adds a variant with option values', function () {
    $product = Product::factory()->withDefaultVariant()->create();
    $option = $product->options()->create(['name' => 'Size']);
    $small = $option->values()->create(['value' => 'Small']);

    $this->actingAs($this->staff)
        ->post(route('admin.products.variants.store', $product), [
            'sku' => 'TEST-SMALL-1',
            'price' => 1500,
            'stock' => 5,
            'option_value_ids' => [$small->id],
        ])
        ->assertRedirect();

    $variant = ProductVariant::query()->firstWhere('sku', 'TEST-SMALL-1');

    expect($variant)->not->toBeNull()
        ->and($variant->optionValues->pluck('id')->all())->toBe([$small->id]);
});

it('keeps exactly one default variant', function () {
    $product = Product::factory()->withDefaultVariant()->create();
    $original = $product->variants()->first();

    $this->actingAs($this->staff)
        ->post(route('admin.products.variants.store', $product), [
            'sku' => 'TEST-NEW-DEFAULT',
            'price' => 1000,
            'stock' => 1,
            'is_default' => true,
        ])
        ->assertRedirect();

    expect($original->fresh()->is_default)->toBeFalse()
        ->and(ProductVariant::query()->firstWhere('sku', 'TEST-NEW-DEFAULT')->is_default)->toBeTrue();
});

it('refuses to delete the last variant', function () {
    $product = Product::factory()->withDefaultVariant()->create();
    $variant = $product->variants()->first();

    $this->actingAs($this->staff)
        ->delete(route('admin.products.variants.destroy', [$product, $variant]))
        ->assertRedirect();

    expect($product->variants()->count())->toBe(1);
});

it('creates an option with values', function () {
    $product = Product::factory()->withDefaultVariant()->create();

    $this->actingAs($this->staff)
        ->post(route('admin.products.options.store', $product), [
            'name' => 'Colour',
            'values' => ['Black', 'White'],
        ])
        ->assertRedirect();

    expect($product->options()->count())->toBe(1)
        ->and($product->options()->first()->values()->pluck('value')->all())->toBe(['Black', 'White']);
});

it('uploads and removes product images', function () {
    Storage::fake('public');

    $product = Product::factory()->withDefaultVariant()->create();

    $this->actingAs($this->staff)
        ->post(route('admin.products.media.store', $product), [
            'images' => [UploadedFile::fake()->image('photo.jpg', 800, 800)],
        ])
        ->assertRedirect();

    $media = $product->fresh()->getMedia('images');
    expect($media)->toHaveCount(1);

    $this->actingAs($this->staff)
        ->delete(route('admin.products.media.destroy', [$product, $media->first()->id]))
        ->assertRedirect();

    expect($product->fresh()->getMedia('images'))->toHaveCount(0);
});

it('manages categories', function () {
    $this->actingAs($this->staff)
        ->post(route('admin.categories.store'), ['name' => 'New Things'])
        ->assertRedirect();

    $category = Category::query()->firstWhere('slug', 'new-things');
    expect($category)->not->toBeNull();

    $this->actingAs($this->staff)
        ->put(route('admin.categories.update', $category), [
            'name' => 'Renamed Things',
            'slug' => 'renamed-things',
            'is_active' => false,
        ])
        ->assertRedirect();

    expect($category->fresh()->name)->toBe('Renamed Things');

    $this->actingAs($this->staff)
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect();

    expect(Category::query()->find($category->id))->toBeNull();
});
