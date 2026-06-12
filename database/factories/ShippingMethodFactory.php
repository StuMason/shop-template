<?php

namespace Database\Factories;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipping_zone_id' => ShippingZone::factory(),
            'name' => 'Standard delivery',
            'description' => '3-5 working days',
            'price' => 395,
            'free_over' => null,
            'is_active' => true,
            'position' => 0,
        ];
    }

    public function freeOver(int $threshold): static
    {
        return $this->state(fn (array $attributes): array => [
            'free_over' => $threshold,
        ]);
    }
}
