<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passport\Client;

beforeEach(function () {
    // The Passport guard needs RSA keys; they're gitignored, so generate them
    // when absent (fresh clones, CI).
    if (! file_exists(storage_path('oauth-public.key'))) {
        $this->artisan('passport:keys');
    }
});

function authorizeUrl(Client $client): string
{
    return '/oauth/authorize?'.http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => $client->redirect_uris[0],
        'response_type' => 'code',
        'state' => 'opaque-state',
    ]);
}

it('renders the consent screen instead of 500ing when a client asks for access', function () {
    $client = Client::factory()->create([
        'name' => 'Claude (MCP)',
        'redirect_uris' => ['https://claude.test/callback'],
    ]);

    $this->actingAs(User::factory()->create())
        ->get(authorizeUrl($client))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/oauth-authorize')
            ->where('client.name', 'Claude (MCP)')
            ->has('authToken')
            ->has('csrfToken')
        );
});

it('approves the request and redirects to the client callback with a code', function () {
    $client = Client::factory()->create([
        'redirect_uris' => ['https://claude.test/callback'],
    ]);

    $this->actingAs(User::factory()->create());

    // The consent screen seats the auth token in the session.
    $this->get(authorizeUrl($client));

    $response = $this->post('/oauth/authorize', [
        'auth_token' => session('authToken'),
    ]);

    $response->assertRedirect();
    expect($response->headers->get('location'))
        ->toStartWith('https://claude.test/callback?')
        ->toContain('code=')
        ->toContain('state=opaque-state');
});

it('denies the request and redirects back with an error', function () {
    $client = Client::factory()->create([
        'redirect_uris' => ['https://claude.test/callback'],
    ]);

    $this->actingAs(User::factory()->create());

    $this->get(authorizeUrl($client));

    $response = $this->delete('/oauth/authorize', [
        'auth_token' => session('authToken'),
    ]);

    $response->assertRedirect();
    expect($response->headers->get('location'))
        ->toStartWith('https://claude.test/callback?')
        ->toContain('error=access_denied');
});
