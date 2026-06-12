<?php

namespace Database\Factories;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_id' => null,
            'subject' => fake()->sentence(4),
            'status' => TicketStatus::Open,
            'last_message_at' => now(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::Closed,
        ]);
    }
}
