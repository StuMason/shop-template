<?php

namespace App\AddressLookup\Providers;

use App\AddressLookup\Contracts\AddressLookupProvider;
use App\AddressLookup\ResolvedAddress;
use App\AddressLookup\Suggestion;
use Illuminate\Support\Str;

/**
 * Deterministic suggestions for tests and local demos — no network, no key.
 */
class FakeProvider implements AddressLookupProvider
{
    public function suggest(string $query, string $country, string $session): array
    {
        return collect([1, 2, 3])
            ->map(fn (int $number): Suggestion => new Suggestion(
                id: "fake-{$number}",
                label: Str::title($query)." Street {$number}, Testville, TE{$number} {$number}ST",
            ))
            ->all();
    }

    public function resolve(string $id, string $session): ?ResolvedAddress
    {
        if (! str_starts_with($id, 'fake-')) {
            return null;
        }

        $number = (int) Str::after($id, 'fake-');

        return new ResolvedAddress(
            line1: "{$number} Fake Street",
            line2: '',
            city: 'Testville',
            county: 'Testshire',
            postcode: "TE{$number} {$number}ST",
            country: 'GB',
        );
    }
}
