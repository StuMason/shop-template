<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

it('creates an admin from the configured credentials', function () {
    config(['shop.admin.email' => 'boss@shop.test', 'shop.admin.password' => 's3cret-pass']);

    $this->artisan('shop:ensure-admin')->assertSuccessful();

    $admin = User::query()->where('email', 'boss@shop.test')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and(Hash::check('s3cret-pass', $admin->password))->toBeTrue();
});

it('rotates the password without duplicating the user', function () {
    config(['shop.admin.email' => 'boss@shop.test', 'shop.admin.password' => 'first-pass']);
    $this->artisan('shop:ensure-admin');

    config(['shop.admin.password' => 'second-pass']);
    $this->artisan('shop:ensure-admin')->assertSuccessful();

    expect(User::query()->where('email', 'boss@shop.test')->count())->toBe(1);

    $admin = User::query()->where('email', 'boss@shop.test')->first();
    expect(Hash::check('second-pass', $admin->password))->toBeTrue();
});

it('strips roles from the placeholder default when a real admin is configured', function () {
    $placeholder = User::factory()->create(['email' => 'admin@example.com']);
    $placeholder->assignRole('admin');

    config(['shop.admin.email' => 'boss@shop.test', 'shop.admin.password' => 'pw']);
    $this->artisan('shop:ensure-admin')->assertSuccessful();

    expect($placeholder->fresh()->hasRole('admin'))->toBeFalse();
});

it('falls back to the placeholder admin for local dev when no password is set', function () {
    config(['shop.admin.email' => 'admin@example.com', 'shop.admin.password' => null]);

    $this->artisan('shop:ensure-admin')->assertSuccessful();

    $admin = User::query()->where('email', 'admin@example.com')->first();

    expect($admin->hasRole('admin'))->toBeTrue()
        ->and(Hash::check('password', $admin->password))->toBeTrue();
});

it('does nothing when no admin email is configured', function () {
    config(['shop.admin.email' => '', 'shop.admin.password' => 'whatever']);

    $this->artisan('shop:ensure-admin')->assertSuccessful();

    expect(User::query()->count())->toBe(0);
});
