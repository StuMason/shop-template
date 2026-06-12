<?php

namespace App\Mcp\Tools;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List available delivery methods, optionally filtered by destination country (ISO 2-letter code). Use the method ID in start-checkout.')]
class ListShippingMethods extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $country = strtoupper((string) $request->get('country', ''));

        $zones = ShippingZone::query()->active()->with('methods')->get();

        if ($country !== '') {
            $zones = $zones->filter(fn (ShippingZone $zone): bool => $zone->servesCountry($country));
        }

        $methods = $zones
            ->flatMap(fn (ShippingZone $zone) => $zone->methods->where('is_active', true))
            ->map(fn (ShippingMethod $method): array => [
                'id' => $method->id,
                'name' => $method->name,
                'description' => $method->description,
                'price_pence' => $method->price,
                'free_over_pence' => $method->free_over,
            ])
            ->values();

        if ($methods->isEmpty()) {
            return Response::error($country !== ''
                ? "No delivery methods serve {$country}."
                : 'No delivery methods are configured.');
        }

        return Response::json($methods->all());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'country' => $schema->string()->description('Optional ISO 3166-1 alpha-2 destination country, e.g. "GB".'),
        ];
    }
}
