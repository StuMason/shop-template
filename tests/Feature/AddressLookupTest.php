<?php

use Illuminate\Support\Facades\Http;

it('returns suggestions from the configured provider', function () {
    config(['services.address_lookup.driver' => 'fake']);

    $this->getJson('/address-lookup?q=high&country=GB&session=test-session')
        ->assertOk()
        ->assertJsonCount(3, 'suggestions')
        ->assertJsonPath('suggestions.0.id', 'fake-1');
});

it('resolves a suggestion to the address snapshot shape', function () {
    config(['services.address_lookup.driver' => 'fake']);

    $this->getJson('/address-lookup/resolve?id=fake-2&session=test-session')
        ->assertOk()
        ->assertJson([
            'address' => [
                'line1' => '2 Fake Street',
                'city' => 'Testville',
                'postcode' => 'TE2 2ST',
                'country' => 'GB',
            ],
        ]);
});

it('degrades to no suggestions when disabled or broken', function () {
    $this->getJson('/address-lookup?q=high&country=GB&session=s')
        ->assertOk()
        ->assertJsonCount(0, 'suggestions');

    $this->getJson('/address-lookup/resolve?id=fake-1&session=s')->assertNotFound();

    config(['services.address_lookup.driver' => 'fake']);
    $this->getJson('/address-lookup/resolve?id=unknown-id&session=s')->assertNotFound();
});

it('maps Google Places responses to suggestions and ISO-coded addresses', function () {
    config([
        'services.address_lookup.driver' => 'google',
        'services.google_places.api_key' => 'test-key',
    ]);

    Http::fake([
        'places.googleapis.com/v1/places:autocomplete' => Http::response([
            'suggestions' => [
                ['placePrediction' => ['placeId' => 'abc123', 'text' => ['text' => '10 Downing Street, London']]],
            ],
        ]),
        'places.googleapis.com/v1/places/abc123*' => Http::response([
            'addressComponents' => [
                ['types' => ['street_number'], 'longText' => '10'],
                ['types' => ['route'], 'longText' => 'Downing Street'],
                ['types' => ['postal_town'], 'longText' => 'London'],
                ['types' => ['postal_code'], 'longText' => 'SW1A 2AA'],
                ['types' => ['country'], 'longText' => 'United Kingdom', 'shortText' => 'GB'],
            ],
        ]),
    ]);

    $this->getJson('/address-lookup?q=10+downing&country=GB&session=s')
        ->assertOk()
        ->assertJsonPath('suggestions.0.label', '10 Downing Street, London');

    $this->getJson('/address-lookup/resolve?id=abc123&session=s')
        ->assertOk()
        ->assertJsonPath('address.line1', '10 Downing Street')
        ->assertJsonPath('address.city', 'London')
        ->assertJsonPath('address.country', 'GB');
});
