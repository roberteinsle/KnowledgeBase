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
