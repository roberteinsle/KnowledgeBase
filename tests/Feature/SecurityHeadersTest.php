<?php

it('sends security headers', function () {
    app()->detectEnvironment(fn () => 'production');

    $response = $this->get('/anmelden');

    $response->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'same-origin');
    expect($response->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'none'")->toContain("default-src 'self'");
});

it('sends HSTS over HTTPS', function () {
    $this->get('https://localhost/anmelden')->assertHeader('Strict-Transport-Security');
});

it('rate limits every route', function () {
    $user = member();
    foreach (range(1, 180) as $i) {
        $this->actingAs($user)->get('/bereiche');
    }

    $this->actingAs($user)->get('/bereiche')->assertTooManyRequests();
});

it('does not leak details in errors', function () {
    config(['app.debug' => false]);

    $this->actingAs(member())->get('/seiten/999999')->assertNotFound()->assertDontSee('ModelNotFoundException');
});
