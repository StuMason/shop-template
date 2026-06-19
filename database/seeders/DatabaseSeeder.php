<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesSeeder::class);

        // The admin user is owned by ADMIN_EMAIL / ADMIN_PASSWORD; the same
        // command runs on every boot, so seeding just shares that one path.
        Artisan::call('shop:ensure-admin');

        $this->call(ShippingSeeder::class);
        $this->call(DemoCatalogueSeeder::class);
    }
}
