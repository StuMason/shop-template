<?php

namespace App\AddressLookup\Providers;

use App\AddressLookup\Contracts\AddressLookupProvider;
use App\AddressLookup\ResolvedAddress;
use App\AddressLookup\Suggestion;
use Illuminate\Support\Facades\Http;

/**
 * Places API (New) over REST, called server-side so the key never reaches
 * the browser. The client passes an opaque session ulid through both calls
 * so Google bills autocomplete + details as one session.
 */
class GooglePlacesProvider implements AddressLookupProvider
{
    public function __construct(
        private readonly string $apiKey,
    ) {}

    public function suggest(string $query, string $country, string $session): array
    {
        $response = Http::timeout(5)->connectTimeout(2)
            ->withHeader('X-Goog-Api-Key', $this->apiKey)
            ->post('https://places.googleapis.com/v1/places:autocomplete', [
                'input' => $query,
                'includedRegionCodes' => [$country],
                'sessionToken' => $session,
            ])
            ->throw();

        /** @var array<int, array{placePrediction?: array{placeId: string, text?: array{text?: string}}}> $rows */
        $rows = $response->json('suggestions') ?? [];

        $suggestions = [];

        foreach ($rows as $row) {
            if (isset($row['placePrediction'])) {
                $suggestions[] = new Suggestion(
                    id: $row['placePrediction']['placeId'],
                    label: $row['placePrediction']['text']['text'] ?? '',
                );
            }
        }

        return $suggestions;
    }

    public function resolve(string $id, string $session): ?ResolvedAddress
    {
        $response = Http::timeout(5)->connectTimeout(2)
            ->withHeader('X-Goog-Api-Key', $this->apiKey)
            ->get("https://places.googleapis.com/v1/places/{$id}", [
                'fields' => 'addressComponents',
                'sessionToken' => $session,
            ]);

        if ($response->failed()) {
            return null;
        }

        /** @var array<int, array{types: array<int, string>, longText?: string, shortText?: string}> $components */
        $components = $response->json('addressComponents') ?? [];

        $text = function (string $type, bool $short = false) use ($components): string {
            foreach ($components as $component) {
                if (in_array($type, $component['types'], true)) {
                    return $short
                        ? ($component['shortText'] ?? '')
                        : ($component['longText'] ?? '');
                }
            }

            return '';
        };

        return new ResolvedAddress(
            line1: trim($text('street_number').' '.$text('route')),
            line2: $text('sublocality_level_1'),
            city: $text('postal_town') !== '' ? $text('postal_town') : $text('locality'),
            county: $text('administrative_area_level_2'),
            postcode: $text('postal_code'),
            country: $text('country', short: true),
        );
    }
}
