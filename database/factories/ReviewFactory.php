<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'order_id' => Order::factory()->paid(),
            'email' => fake()->safeEmail(),
            'name' => fake()->firstName(),
            'rating' => fake()->numberBetween(3, 5),
            'body' => fake()->sentence(12),
            'is_published' => true,
        ];
    }
}
