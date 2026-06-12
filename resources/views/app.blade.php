<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- Baseline SEO. Pages override title/description/canonical via the <Seo> component. --}}
        <meta name="description" content="{{ config('shop.description') }}" inertia="description">
        <link rel="canonical" href="{{ url()->current() }}" inertia="canonical">
        <meta property="og:site_name" content="{{ app(\App\Support\ShopSettings::class)->name() }}">
        <meta property="og:type" content="website" inertia="og:type">

        {{-- Site-level structured data, driven by shop settings. --}}
        @php
            $shopSettings = app(\App\Support\ShopSettings::class);
            $siteJsonLd = [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'WebSite',
                        'name' => $shopSettings->name(),
                        'url' => config('app.url'),
                    ],
                    [
                        '@type' => 'Organization',
                        'name' => $shopSettings->name(),
                        'url' => config('app.url'),
                        'email' => $shopSettings->contactEmail(),
                    ],
                ],
            ];
        @endphp
        <script type="application/ld+json">{!! json_encode($siteJsonLd, JSON_UNESCAPED_SLASHES) !!}</script>

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
