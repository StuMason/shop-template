<?php

use App\Support\ShopSettings;

it('falls back to config defaults when no setting is stored', function () {
    config(['shop.name' => 'Config Shop']);

    expect(app(ShopSettings::class)->name())->toBe('Config Shop');
});

it('persists settings and overrides config defaults', function () {
    config(['shop.name' => 'Config Shop']);

    $settings = app(ShopSettings::class);
    $settings->set('name', 'Runtime Shop');

    expect($settings->name())->toBe('Runtime Shop')
        ->and(app(ShopSettings::class)->tagline())->toBe(config('shop.tagline'));
});

it('updates existing settings in place', function () {
    $settings = app(ShopSettings::class);

    $settings->set('name', 'First');
    $settings->set('name', 'Second');

    expect($settings->name())->toBe('Second');

    $this->assertDatabaseCount('shop_settings', 1);
});
