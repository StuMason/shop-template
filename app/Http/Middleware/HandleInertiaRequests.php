<?php

namespace App\Http\Middleware;

use App\Actions\Cart\ResolveCart;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Resources\CartResource;
use App\Support\ShopSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $settings = app(ShopSettings::class);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'shop' => [
                'name' => $settings->name(),
                'tagline' => $settings->tagline(),
                'currency' => $settings->currency(),
                'contact_email' => $settings->contactEmail(),
                'trading_details' => $settings->tradingDetails(),
                'url' => rtrim((string) config('app.url'), '/'),
                'pages' => PageController::available(),
            ],
            'auth' => [
                'user' => $request->user(),
                'isStaff' => $request->user()?->hasAnyRole(['admin', 'staff']) ?? false,
                'isAdmin' => $request->user()?->hasRole('admin') ?? false,
            ],
            'basket' => fn (): ?array => $this->basket($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * The current basket payload, or null when the visitor has none yet.
     *
     * @return array<string, mixed>|null
     */
    protected function basket(Request $request): ?array
    {
        if (! $request->hasSession()) {
            return null;
        }

        $cart = app(ResolveCart::class)->find(
            $request->user(),
            $request->session()->get('cart_token'),
        );

        return $cart !== null ? (new CartResource($cart))->resolve() : null;
    }
}
