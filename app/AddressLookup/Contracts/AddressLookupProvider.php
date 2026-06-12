<?php

namespace App\AddressLookup\Contracts;

use App\AddressLookup\ResolvedAddress;
use App\AddressLookup\Suggestion;

interface AddressLookupProvider
{
    /**
     * Type-ahead suggestions for a partial address.
     *
     * @return array<int, Suggestion>
     */
    public function suggest(string $query, string $country, string $session): array;

    /**
     * Resolve a suggestion id to a full address, or null if it vanished.
     */
    public function resolve(string $id, string $session): ?ResolvedAddress;
}
