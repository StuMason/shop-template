<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ShopSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * The shop settings form.
     */
    public function edit(ShopSettings $settings): Response
    {
        return Inertia::render('admin/settings', [
            'settings' => [
                'name' => $settings->name(),
                'tagline' => $settings->tagline(),
                'description' => $settings->description(),
                'contact_email' => $settings->contactEmail(),
                'order_prefix' => $settings->orderPrefix(),
                'trading_details' => $settings->tradingDetails() ?? '',
                'vat_registered' => $settings->vatRegistered(),
                'vat_number' => $settings->vatNumber() ?? '',
                'vat_rate' => $settings->vatRate(),
            ],
            'currency' => $settings->currency(),
        ]);
    }

    /**
     * Persist runtime shop settings.
     */
    public function update(Request $request, ShopSettings $settings): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'contact_email' => ['required', 'email', 'max:255'],
            'order_prefix' => ['required', 'string', 'alpha_num:ascii', 'max:8'],
            'trading_details' => ['nullable', 'string', 'max:500'],
            'vat_registered' => ['boolean'],
            'vat_number' => ['nullable', 'string', 'max:32', 'required_if:vat_registered,true'],
            'vat_rate' => ['numeric', 'min:0', 'max:100'],
        ]);

        $settings->setMany($validated);

        return back()->with('success', 'Shop settings saved.');
    }
}
