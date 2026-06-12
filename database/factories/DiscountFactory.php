<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##??')),
            'type' => DiscountType::Percent,
            'value' => 10,
            'min_subtotal' => null,
            'starts_at' => null,
            'ends_at' => null,
            'max_uses' => null,
            'used_count' => 0,
            'is_active' => true,
        ];
    }

    public function fixed(int $pence): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => DiscountType::Fixed,
            'value' => $pence,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ends_at' => now()->subDay(),
        ]);
    }
}
