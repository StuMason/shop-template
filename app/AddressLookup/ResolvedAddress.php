<?php

namespace App\AddressLookup;

/**
 * Matches the address snapshot shape used by checkout and the address book.
 */
readonly class ResolvedAddress
{
    public function __construct(
        public string $line1,
        public string $line2,
        public string $city,
        public string $county,
        public string $postcode,
        public string $country,
    ) {}

    /**
     * @return array{line1: string, line2: string, city: string, county: string, postcode: string, country: string}
     */
    public function toArray(): array
    {
        return [
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'county' => $this->county,
            'postcode' => $this->postcode,
            'country' => $this->country,
        ];
    }
}
