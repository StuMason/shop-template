<?php

namespace Database\Seeders;

use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    /**
     * A sensible UK default so a fresh clone can check out immediately.
     * Idempotent.
     */
    public function run(): void
    {
        $zone = ShippingZone::query()->firstOrCreate(
            ['name' => 'United Kingdom'],
            ['countries' => ['GB'], 'is_active' => true],
        );

        $zone->methods()->firstOrCreate(
            ['name' => 'Standard delivery'],
            [
                'description' => '3–5 working days',
                'price' => 395,
                'free_over' => 5000,
                'is_active' => true,
                'position' => 0,
            ],
        );

        $zone->methods()->firstOrCreate(
            ['name' => 'Express delivery'],
            [
                'description' => 'Next working day when ordered before 2pm',
                'price' => 795,
                'free_over' => null,
                'is_active' => true,
                'position' => 1,
            ],
        );
    }
}
