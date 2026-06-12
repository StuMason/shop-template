<?php

namespace App\AddressLookup;

use App\AddressLookup\Contracts\AddressLookupProvider;
use App\AddressLookup\Providers\FakeProvider;
use App\AddressLookup\Providers\GooglePlacesProvider;
use Illuminate\Support\Manager;

/**
 * Same swappable-provider shape as PaymentManager. ADDRESS_LOOKUP picks the
 * driver; "none" disables the feature and the frontend hides the search box.
 */
class AddressLookupManager extends Manager
{
    public function enabled(): bool
    {
        return $this->getDefaultDriver() !== 'none';
    }

    public function getDefaultDriver(): string
    {
        return (string) ($this->config->get('services.address_lookup.driver') ?? 'none');
    }

    protected function createGoogleDriver(): AddressLookupProvider
    {
        return new GooglePlacesProvider(
            apiKey: (string) $this->config->get('services.google_places.api_key'),
        );
    }

    protected function createFakeDriver(): AddressLookupProvider
    {
        return new FakeProvider;
    }
}
