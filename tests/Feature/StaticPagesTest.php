<?php

it('serves the seeded legal pages with SSR-safe markdown', function (string $page) {
    $this->get(route('pages.show', $page))
        ->assertOk()
        ->assertInertia(fn ($assert) => $assert
            ->component('storefront/pages/show')
            ->where('page.slug', $page));
})->with(['terms', 'privacy', 'returns', 'about']);

it('states the 14-day cancellation right on the returns page', function () {
    $response = $this->get(route('pages.show', 'returns'))->assertOk();

    expect($response->inertiaPage()['props']['page']['html'])
        ->toContain('14 days');
});

it('404s for unknown pages', function () {
    $this->get(route('pages.show', 'not-a-page'))->assertNotFound();
});

it('strips raw html from page markdown', function () {
    file_put_contents(resource_path('markdown/xss-test.md'), "# Test\n\n<script>alert(1)</script>\n");

    try {
        $response = $this->get(route('pages.show', 'xss-test'))->assertOk();

        expect($response->inertiaPage()['props']['page']['html'])
            ->not->toContain('<script>');
    } finally {
        unlink(resource_path('markdown/xss-test.md'));
    }
});

it('lists pages in the shared shop prop and the sitemap', function () {
    $this->get(route('home'))
        ->assertInertia(fn ($assert) => $assert
            ->where('shop.pages', ['about', 'privacy', 'returns', 'terms']));

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee('/pages/terms');
});
