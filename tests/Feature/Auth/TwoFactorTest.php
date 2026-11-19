<?php

use App\Modules\Auth\Services\TotpService;
use App\Modules\Workspace\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Passkeys;
use PragmaRX\Google2FA\Google2FA;

function userWithTotp(): array
{
    $user = member();
    $secret = app(TotpService::class)->generateSecret();
    $codes = app(TotpService::class)->enable($user, $secret);

    return [$user->fresh(), $secret, $codes];
}

it('asks for the second factor after a magic link', function () {
    [$user] = userWithTotp();
    $token = magicToken($user);

    $this->post("/login/link/{$token}")->assertRedirect(route('two-factor.show'));

    $this->assertGuest();
    $this->get(route('two-factor.show'))->assertOk();
});

it('rejects a wrong code', function () {
    [$user] = userWithTotp();
    $this->post('/login/link/'.magicToken($user));

    $this->post(route('two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('signs in with a valid code, but only once per code', function () {
    [$user, $secret] = userWithTotp();
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $this->post('/login/link/'.magicToken($user));
    $this->post(route('two-factor.verify'), ['code' => $code])->assertRedirect();
    $this->assertAuthenticatedAs($user);

    auth()->logout();
    $this->post('/login/link/'.magicToken($user));
    $this->post(route('two-factor.verify'), ['code' => $code])->assertSessionHasErrors('code');
});

it('accepts each recovery code once', function () {
    [$user, , $codes] = userWithTotp();

    $this->post('/login/link/'.magicToken($user));
    $this->post(route('two-factor.verify'), ['code' => strtolower($codes[0])])->assertRedirect();
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->totp_recovery_codes)->toHaveCount(7);

    auth()->logout();
    $this->post('/login/link/'.magicToken($user));
    $this->post(route('two-factor.verify'), ['code' => $codes[0]])->assertSessionHasErrors('code');
});

it('rate limits the code entry', function () {
    [$user] = userWithTotp();
    $this->post('/login/link/'.magicToken($user));

    foreach (range(1, 5) as $i) {
        $this->post(route('two-factor.verify'), ['code' => '000000']);
    }

    $this->post(route('two-factor.verify'), ['code' => '000000'])->assertTooManyRequests();
});

it('does not allow the challenge without a pending sign-in', function () {
    $this->get(route('two-factor.show'))->assertRedirect('/login');
    $this->post(route('two-factor.verify'), ['code' => '123456'])->assertRedirect('/login');
});

it('stores the TOTP secret encrypted', function () {
    [$user, $secret] = userWithTotp();

    $raw = DB::table('users')->where('id', $user->id)->value('totp_secret');
    expect($raw)->not->toContain($secret);
});

it('lets users enable two-factor authentication after confirming the code', function () {
    $user = member();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

    $this->post(route('account.totp.start'))->assertRedirect(route('account.security'));
    $secret = session('auth.totp_setup_secret');
    expect($secret)->toBeString();

    $this->post(route('account.totp.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');
    expect($user->fresh()->hasTotp())->toBeFalse();

    Cache::flush();
    $this->post(route('account.totp.confirm'), ['code' => app(Google2FA::class)->getCurrentOtp($secret)])
        ->assertRedirect(route('account.security'))
        ->assertSessionHas('auth.recovery_codes');
    expect($user->fresh()->hasTotp())->toBeTrue();
});

it('requires a fresh confirmation for security settings', function () {
    $user = member();

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time() - 3600])
        ->get(route('account.security'))
        ->assertRedirect(route('password.confirm'));

    $this->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('account.security'))
        ->assertOk();
});

it('counts a sign-in as fresh confirmation', function () {
    $user = member();
    $user->passkeys()->create(['name' => 'Test', 'credential_id' => 'abc', 'credential' => []]);

    $this->post('/login/link/'.magicToken($user));

    $this->get(route('account.security'))->assertOk();
});

it('sends users without second factor to the security page when MFA is enforced', function () {
    Workspace::current()->forceFill(['enforce_mfa' => true])->save();
    $user = member();

    $this->actingAs($user)->get('/')->assertRedirect(route('account.security'));

    [$withTotp] = userWithTotp();
    $this->actingAs($withTotp)->get('/')->assertOk();
});

it('signs out deactivated users on their next request', function () {
    $user = member();
    $this->actingAs($user);
    $user->forceFill(['is_active' => false])->save();

    $this->get('/')->assertRedirect('/login');
    $this->assertGuest();
});

it('does not let a deactivated account in with a passkey', function () {
    $user = member(['is_active' => false]);
    $passkey = $user->passkeys()->create(['name' => 'Test', 'credential_id' => 'abc', 'credential' => []]);

    expect(fn () => Passkeys::allowsLogin(request(), $passkey))
        ->toThrow(ValidationException::class);
});

it('serves passkey login options to guests', function () {
    $this->getJson('/passkeys/login/options')->assertOk()->assertJsonStructure(['options' => ['challenge']]);
});

it('requires a fresh confirmation before a passkey can be registered', function () {
    $user = member();

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time() - 3600])
        ->getJson('/user/passkeys/options')->assertStatus(423);

    $this->withSession(['auth.password_confirmed_at' => time()])
        ->getJson('/user/passkeys/options')->assertOk();
});
