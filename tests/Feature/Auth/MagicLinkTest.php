<?php

use App\Core\Activity\Activity;
use App\Models\User;
use App\Modules\Auth\Mail\MagicLinkMail;
use App\Modules\Auth\Models\MagicLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Mail::fake();
    $this->withoutDefer();
});

it('shows the sign-in page to guests', function () {
    $this->get('/login')->assertOk()->assertSee(__('Send sign-in link'));
});

it('redirects guests to the sign-in page', function () {
    $this->get('/')->assertRedirect('/login');
    $this->get('/admin')->assertRedirect('/login');
});

it('answers identically for known and unknown addresses', function () {
    member(['email' => 'max@example.com']);

    $known = $this->post('/login/link', ['email' => 'max@example.com']);
    $unknown = $this->post('/login/link', ['email' => 'nobody@example.com']);

    $known->assertRedirect('/login')->assertSessionHas('status');
    $unknown->assertRedirect('/login')->assertSessionHas('status');
    expect($known->getSession()->get('status'))->toBe($unknown->getSession()->get('status'));

    Mail::assertSent(MagicLinkMail::class, 1);
    Mail::assertSent(MagicLinkMail::class, fn ($mail) => $mail->hasTo('max@example.com'));
});

it('normalises the address before the lookup', function () {
    member(['email' => 'max@example.com']);

    $this->post('/login/link', ['email' => '  MAX@Example.COM ']);

    Mail::assertSent(MagicLinkMail::class, fn ($mail) => $mail->hasTo('max@example.com'));
});

it('validates the address on the server', function (string $email) {
    $this->post('/login/link', ['email' => $email])->assertSessionHasErrors('email');
    Mail::assertNothingSent();
})->with(['', 'no-address', str_repeat('a', 250).'@example.com']);

it('stores only the SHA-256 hash of the token', function () {
    $user = member(['email' => 'max@example.com']);

    $this->post('/login/link', ['email' => 'max@example.com']);

    $url = null;
    Mail::assertSent(MagicLinkMail::class, function (MagicLinkMail $mail) use (&$url) {
        $url = $mail->url;

        return true;
    });

    $token = basename($url);
    expect($token)->toMatch('/^[0-9a-f]{64}$/');
    $link = MagicLink::query()->where('user_id', $user->id)->sole();
    expect($link->token_hash)->toBe(hash('sha256', $token))->not->toBe($token);
    expect(DB::table('magic_links')->where('token_hash', $token)->exists())->toBeFalse();
});

it('builds the link from APP_URL, not from the Host header', function () {
    config(['app.url' => 'https://kb.example.com']);
    member(['email' => 'max@example.com']);

    $this->withHeader('Host', 'evil.example.net')->post('/login/link', ['email' => 'max@example.com']);

    Mail::assertSent(MagicLinkMail::class, fn (MagicLinkMail $mail) => str_starts_with($mail->url, 'https://kb.example.com/login/link/'));
});

it('sends nothing to deactivated accounts', function () {
    member(['email' => 'max@example.com', 'is_active' => false]);

    $this->post('/login/link', ['email' => 'max@example.com'])->assertSessionHas('status');

    Mail::assertNothingSent();
});

it('shows only a confirmation button on GET and does not use the token', function () {
    $user = member();
    $token = magicToken($user);

    $this->get("/login/link/{$token}")
        ->assertOk()
        ->assertSee(__('Sign in now'))
        ->assertHeader('Referrer-Policy', 'no-referrer');

    $this->assertGuest();
    expect(MagicLink::query()->whereNull('used_at')->count())->toBe(1);
});

it('signs in with a valid link and regenerates the session', function () {
    $user = member();
    $token = magicToken($user);
    $this->get('/login');
    $before = session()->getId();

    $this->post("/login/link/{$token}")->assertRedirect();

    $this->assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($before);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('offers a passkey after the first magic link sign-in', function () {
    $token = magicToken(member());

    $this->post("/login/link/{$token}")->assertRedirect(route('account.passkey-offer'));
});

it('accepts a link only once', function () {
    $user = member();
    $token = magicToken($user);

    $this->post("/login/link/{$token}");
    auth()->logout();

    $this->post("/login/link/{$token}")->assertRedirect('/login')->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('rejects expired links', function () {
    $token = magicToken(member(), ['expires_at' => now()->subMinute()]);

    $this->post("/login/link/{$token}")->assertRedirect('/login')->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('rejects used links', function () {
    $token = magicToken(member(), ['used_at' => now()->subMinute()]);

    $this->post("/login/link/{$token}")->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('rejects manipulated tokens', function () {
    $token = magicToken(member());
    $manipulated = substr($token, 0, -1).($token[-1] === 'a' ? 'b' : 'a');

    $this->post("/login/link/{$manipulated}")->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('answers malformed tokens with 404 before any query runs', function (string $token) {
    DB::enableQueryLog();

    $this->get('/login/link/'.$token)->assertNotFound();
    $this->post('/login/link/'.$token)->assertNotFound();

    expect(collect(DB::getQueryLog())->pluck('query')->filter(fn ($q) => str_contains($q, 'magic_links')))->toBeEmpty();
    $this->assertGuest();
})->with([
    'too short' => str_repeat('a', 63),
    'too long' => str_repeat('a', 65),
    'upper case' => str_repeat('A', 64),
    'non hex' => str_repeat('g', 64),
]);

it('is not fooled by SQL injection in the e-mail field', function (string $payload) {
    member(['email' => 'max@example.com']);
    admin();

    $response = $this->post('/login/link', ['email' => $payload]);

    expect($response->status())->toBeLessThan(500);
    expect($response->getContent())->not->toContain('SQLSTATE')->not->toContain('syntax error');
    $this->assertGuest();
    Mail::assertNothingSent();
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(User::count())->toBe(2);
})->with(sqlInjectionPayloads());

it('is not fooled by SQL injection in the token', function (string $payload) {
    magicToken(member());

    foreach (['get', 'post'] as $method) {
        $response = $this->{$method}('/login/link/'.$payload);

        expect($response->status())->toBeLessThan(500);
        expect($response->getContent())->not->toContain('SQLSTATE');
    }

    $this->assertGuest();
    expect(Schema::hasTable('users'))->toBeTrue();
    expect(MagicLink::query()->whereNull('used_at')->count())->toBe(1);
})->with(sqlInjectionPayloads());

it('invalidates older open links when a new one is requested', function () {
    $user = member(['email' => 'max@example.com']);
    $old = magicToken($user);

    $this->post('/login/link', ['email' => 'max@example.com']);

    $this->post("/login/link/{$old}")->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('deletes all other links after a successful sign-in', function () {
    $user = member();
    $first = magicToken($user);
    magicToken($user);

    $this->post("/login/link/{$first}");

    expect(MagicLink::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('rate limits link requests per address', function () {
    member(['email' => 'max@example.com']);

    foreach (range(1, 5) as $i) {
        $this->post('/login/link', ['email' => 'max@example.com'])->assertRedirect();
    }

    $this->post('/login/link', ['email' => 'max@example.com'])->assertTooManyRequests();
});

it('rate limits link requests per IP', function () {
    foreach (range(1, 30) as $i) {
        $this->post('/login/link', ['email' => "user{$i}@example.com"])->assertRedirect();
    }

    $this->post('/login/link', ['email' => 'another@example.com'])->assertTooManyRequests();
});

it('rate limits redeeming links', function () {
    foreach (range(1, 10) as $i) {
        $this->post('/login/link/'.str_repeat('a', 64))->assertRedirect();
    }

    $this->post('/login/link/'.str_repeat('a', 64))->assertTooManyRequests();
});

it('only redirects to internal paths after sign-in', function (string $target, string $expected) {
    $user = member();
    $user->passkeys()->create(['name' => 'Test', 'credential_id' => 'abc', 'credential' => []]);
    $token = magicToken($user, ['redirect_to' => $target]);

    $this->post("/login/link/{$token}")->assertRedirect($expected);
})->with([
    'internal' => ['/search?q=test', '/search?q=test'],
]);

it('drops external redirect targets before they are stored', function (string $target) {
    member(['email' => 'max@example.com']);

    $this->get('/login?next='.urlencode($target));
    $this->post('/login/link', ['email' => 'max@example.com']);

    expect(MagicLink::query()->sole()->redirect_to)->toBeNull();
})->with(['https://evil.example.net', '//evil.example.net', '/\\evil.example.net', 'javascript:alert(1)']);

it('never writes the token to the log or the activity log', function () {
    Log::spy();
    member(['email' => 'max@example.com']);

    $this->post('/login/link', ['email' => 'max@example.com']);

    $url = null;
    Mail::assertSent(MagicLinkMail::class, function (MagicLinkMail $mail) use (&$url) {
        $url = $mail->url;

        return true;
    });
    $token = basename($url);

    expect(Activity::query()->get()->toJson())->not->toContain($token);
    Log::shouldNotHaveReceived('info', fn ($message) => str_contains((string) $message, $token));
});

it('creates the first admin from ADMIN_EMAIL', function () {
    config(['knowledgebase.admin_email' => 'Chef@Example.com']);

    $this->post('/login/link', ['email' => 'chef@example.com']);

    $user = User::query()->where('email', 'chef@example.com')->sole();
    expect($user->isAdmin())->toBeTrue();
    Mail::assertSent(MagicLinkMail::class, fn ($mail) => $mail->hasTo('chef@example.com'));
});

it('does not create accounts for other addresses', function () {
    config(['knowledgebase.admin_email' => 'chef@example.com']);

    $this->post('/login/link', ['email' => 'someone@example.com']);

    expect(User::count())->toBe(0);
});

it('keeps answering when the mail cannot be sent', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));
    member(['email' => 'max@example.com']);

    $this->post('/login/link', ['email' => 'max@example.com'])->assertRedirect('/login')->assertSessionHas('status');
});

it('signs out', function () {
    $this->actingAs(member())->post('/logout')->assertRedirect('/login');
    $this->assertGuest();
});
