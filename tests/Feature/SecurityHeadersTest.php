<?php

it('sends security headers', function () {
    app()->detectEnvironment(fn () => 'production');

    $response = $this->get('/login');

    $response->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'same-origin');
    expect($response->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'none'")->toContain("default-src 'self'");
});

it('sends HSTS over HTTPS', function () {
    $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security');
});

it('rate limits every route', function () {
    $user = member();
    foreach (range(1, 180) as $i) {
        $this->actingAs($user)->get('/spaces');
    }

    $this->actingAs($user)->get('/spaces')->assertTooManyRequests();
});

it('does not leak details in errors', function () {
    config(['app.debug' => false]);

    $this->actingAs(member())->get('/pages/999999')->assertNotFound()->assertDontSee('ModelNotFoundException');
});

it('allows blob workers for uploads but nothing from other origins', function () {
    app()->detectEnvironment(fn () => 'production');

    $csp = $this->get('/login')->headers->get('Content-Security-Policy');

    expect($csp)->toContain("worker-src 'self' blob:")->toContain("img-src 'self' data: blob:")->not->toContain('https:');
});

it('allows the OAuth callback as form target on the consent page only', function () {
    app()->detectEnvironment(fn () => 'production');
    $clientId = $this->postJson('/oauth/register', ['client_name' => 'Claude', 'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback']])->json('client_id');
    $query = http_build_query([
        'client_id' => $clientId, 'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback', 'response_type' => 'code',
        'scope' => 'mcp:use', 'state' => 'x', 'code_challenge' => str_repeat('a', 43), 'code_challenge_method' => 'S256',
    ]);

    $consent = $this->actingAs(member())->get('/oauth/authorize?'.$query)->assertOk();
    expect($consent->headers->get('Content-Security-Policy'))->toContain("form-action 'self' https://claude.ai;");

    expect($this->get('/account')->headers->get('Content-Security-Policy'))->toContain("form-action 'self';");
});
