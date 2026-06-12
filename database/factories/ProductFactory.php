<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucwords(fake()->unique()->word().' '.fake()->word().' '.fake()->word());

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraphs(2, true),
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Archived,
        ]);
    }

    /**
     * Give the product a single default variant (a "simple" product).
     */
    public function withDefaultVariant(?int $price = null, int $stock = 10): static
    {
        return $this->has(
            ProductVariant::factory()->state(fn (): array => [
                'is_default' => true,
                'price' => $price ?? fake()->numberBetween(500, 20000),
                'stock' => $stock,
            ]),
            'variants',
        );
    }
}
