<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\OrderNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(1000, 20000);
        $shipping = 395;

        return [
            'number' => OrderNumber::generate(),
            'user_id' => null,
            'cart_id' => null,
            'email' => fake()->safeEmail(),
            'status' => OrderStatus::Pending,
            'currency' => 'GBP',
            'subtotal' => $subtotal,
            'shipping_total' => $shipping,
            'total' => $subtotal + $shipping,
            'shipping_method_name' => 'Standard delivery',
            'shipping_address' => $this->addressSnapshot(),
            'billing_address' => $this->addressSnapshot(),
            'customer_note' => null,
            'placed_at' => now(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    protected function addressSnapshot(): array
    {
        return [
            'name' => fake()->name(),
            'line1' => fake()->streetAddress(),
            'line2' => null,
            'city' => fake()->city(),
            'county' => null,
            'postcode' => strtoupper(fake()->bothify('??# #??')),
            'country' => 'GB',
            'phone' => null,
        ];
    }
}
