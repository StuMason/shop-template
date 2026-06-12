<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShippingController extends Controller
{
    /**
     * Zones and their methods on one screen.
     */
    public function index(): Response
    {
        $zones = ShippingZone::query()
            ->with('methods')
            ->orderBy('name')
            ->get()
            ->map(fn (ShippingZone $zone): array => [
                'id' => $zone->id,
                'name' => $zone->name,
                'countries' => $zone->countries,
                'is_active' => $zone->is_active,
                'methods' => $zone->methods->map(fn (ShippingMethod $method): array => [
                    'id' => $method->id,
                    'name' => $method->name,
                    'description' => $method->description,
                    'price' => $method->price,
                    'free_over' => $method->free_over,
                    'is_active' => $method->is_active,
                ])->all(),
            ]);

        return Inertia::render('admin/shipping', [
            'zones' => $zones,
        ]);
    }

    public function storeZone(Request $request): RedirectResponse
    {
        $validated = $this->validateZone($request);

        ShippingZone::create($validated);

        return back()->with('success', 'Shipping zone created.');
    }

    public function updateZone(Request $request, ShippingZone $zone): RedirectResponse
    {
        $zone->update($this->validateZone($request));

        return back()->with('success', 'Shipping zone updated.');
    }

    public function destroyZone(ShippingZone $zone): RedirectResponse
    {
        $zone->delete();

        return back()->with('success', 'Shipping zone deleted.');
    }

    public function storeMethod(Request $request, ShippingZone $zone): RedirectResponse
    {
        $zone->methods()->create($this->validateMethod($request));

        return back()->with('success', 'Shipping method created.');
    }

    public function updateMethod(Request $request, ShippingZone $zone, ShippingMethod $method): RedirectResponse
    {
        abort_unless($method->shipping_zone_id === $zone->id, 404);

        $method->update($this->validateMethod($request));

        return back()->with('success', 'Shipping method updated.');
    }

    public function destroyMethod(ShippingZone $zone, ShippingMethod $method): RedirectResponse
    {
        abort_unless($method->shipping_zone_id === $zone->id, 404);

        $method->delete();

        return back()->with('success', 'Shipping method deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateZone(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'countries' => ['required', 'array', 'min:1'],
            'countries.*' => ['string', 'size:2'],
            'is_active' => ['boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateMethod(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'free_over' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
