<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'gateway' => 'fake',
            'status' => PaymentStatus::Pending,
            'amount' => fake()->numberBetween(1000, 20000),
            'currency' => 'GBP',
            'idempotency_key' => (string) Str::ulid(),
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Succeeded,
        ]);
    }
}
