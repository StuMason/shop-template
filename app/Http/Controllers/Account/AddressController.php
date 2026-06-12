<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AddressRequest;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    /**
     * The customer's address book.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('account/addresses', [
            'addresses' => $request->user()->addresses()->latest()->get(),
        ]);
    }

    /**
     * Add an address.
     */
    public function store(AddressRequest $request): RedirectResponse
    {
        $address = $request->user()->addresses()->create($request->validated());

        $this->ensureSingleDefaults($address);

        return back()->with('success', 'Address saved.');
    }

    /**
     * Update an address.
     */
    public function update(AddressRequest $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        $address->update($request->validated());

        $this->ensureSingleDefaults($address);

        return back()->with('success', 'Address updated.');
    }

    /**
     * Remove an address.
     */
    public function destroy(Request $request, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        $address->delete();

        return back()->with('success', 'Address removed.');
    }

    /**
     * Only one default shipping/billing address per user.
     */
    protected function ensureSingleDefaults(Address $address): void
    {
        if ($address->is_default_shipping) {
            $address->user->addresses()
                ->whereKeyNot($address->id)
                ->update(['is_default_shipping' => false]);
        }

        if ($address->is_default_billing) {
            $address->user->addresses()
                ->whereKeyNot($address->id)
                ->update(['is_default_billing' => false]);
        }
    }
}
