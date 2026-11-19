<?php

use App\Modules\Auth\Models\MagicLink;
use App\Modules\Spaces\Enums\SpaceRole;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

function mcpCall(string $method, array $params = [], array $headers = []): array
{
    return [
        'json' => ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params],
        'headers' => ['Accept' => 'application/json, text/event-stream', ...$headers],
    ];
}

it('publishes OAuth metadata for MCP clients', function () {
    $this->getJson('/.well-known/oauth-protected-resource/mcp')->assertOk()->assertJsonPath('resource', url('/mcp'));
    $this->getJson('/.well-known/oauth-authorization-server')->assertOk()
        ->assertJsonPath('authorization_endpoint', route('passport.authorizations.authorize'))
        ->assertJsonPath('registration_endpoint', route('mcp.oauth.register'))
        ->assertJsonPath('code_challenge_methods_supported', ['S256']);
});

it('rejects MCP requests without a token and points to the metadata', function () {
    $call = mcpCall('tools/list');

    $this->postJson('/mcp', $call['json'], $call['headers'])
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate');
});

it('registers clients only for allowed callback domains', function () {
    $this->postJson('/oauth/register', ['client_name' => 'Claude', 'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback']])
        ->assertSuccessful()->assertJsonStructure(['client_id']);
    $this->postJson('/oauth/register', ['client_name' => 'Claude Code', 'redirect_uris' => ['http://localhost:33418/callback']])
        ->assertSuccessful();

    $this->postJson('/oauth/register', ['client_name' => 'Phisher', 'redirect_uris' => ['https://evil.example.net/callback']])
        ->assertStatus(400)->assertJsonPath('error', 'invalid_redirect_uri');
});

it('rate limits client registration', function () {
    foreach (range(1, 30) as $i) {
        $this->postJson('/oauth/register', ['client_name' => "C{$i}", 'redirect_uris' => ['https://claude.ai/cb']]);
    }

    $this->postJson('/oauth/register', ['client_name' => 'C31', 'redirect_uris' => ['https://claude.ai/cb']])->assertTooManyRequests();
});

it('runs the complete OAuth flow and calls a tool with the token', function () {
    Artisan::call('passport:keys', ['--force' => true]);
    $user = member(['email' => 'max@example.com']);
    $user->passkeys()->create(['name' => 'x', 'credential_id' => 'x', 'credential' => []]);
    page(space([[$user, SpaceRole::Reader]], ['name' => 'Handbuch']), ['title' => 'Willkommen']);

    $clientId = $this->postJson('/oauth/register', ['client_name' => 'Claude', 'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback']])->json('client_id');

    $verifier = Str::random(64);
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    $query = http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => 'abc',
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
    ]);

    // Guests are sent to the normal sign-in; the long authorize URL survives the magic link.
    $this->get('/oauth/authorize?'.$query)->assertRedirect('/login');
    Mail::fake();
    $this->withoutDefer()->post('/login/link', ['email' => 'max@example.com']);
    $link = MagicLink::query()->sole();
    expect($link->redirect_to)->toStartWith('/oauth/authorize?')->toContain('code_challenge=');

    $this->actingAs($user);
    $this->get('/oauth/authorize?'.$query)->assertOk()->assertSee('Claude')->assertSee('claude.ai');

    $authToken = session('authToken');
    $approve = $this->post(route('passport.authorizations.approve'), ['state' => 'abc', 'client_id' => $clientId, 'auth_token' => $authToken]);
    $location = $approve->headers->get('Location');
    expect($location)->toStartWith('https://claude.ai/api/mcp/auth_callback?');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $params);
    expect($params['state'])->toBe('abc');

    auth('web')->logout();
    $token = $this->postJson('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $clientId,
        'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
        'code_verifier' => $verifier,
        'code' => $params['code'],
    ])->assertOk()->json();
    expect($token)->toHaveKeys(['access_token', 'refresh_token', 'expires_in']);
    expect($token['expires_in'])->toBeLessThanOrEqual(3600);

    $call = mcpCall('initialize', ['protocolVersion' => '2025-06-18', 'capabilities' => (object) [], 'clientInfo' => ['name' => 'test', 'version' => '1']]);
    $this->postJson('/mcp', $call['json'], [...$call['headers'], 'Authorization' => 'Bearer '.$token['access_token']])->assertOk();

    $call = mcpCall('tools/call', ['name' => 'search', 'arguments' => ['query' => 'willkommen']], ['MCP-Protocol-Version' => '2025-06-18']);
    $this->app['auth']->forgetGuards();
    $this->postJson('/mcp', $call['json'], [...$call['headers'], 'Authorization' => 'Bearer '.$token['access_token']])
        ->assertOk()->assertSee('Willkommen');

    // Revoking in the account page ends the access.
    $this->actingAs($user)->get('/account')->assertSee(__('Connected AI apps'))->assertSee('Claude');
    $this->actingAs($user)->delete(route('mcp.connections.destroy', $clientId))->assertRedirect();
    auth('web')->logout();
    $this->app['auth']->forgetGuards();
    $this->postJson('/mcp', $call['json'], [...$call['headers'], 'Authorization' => 'Bearer '.$token['access_token']])->assertUnauthorized();
});

it('blocks deactivated users even with a valid token', function () {
    $user = member(['is_active' => false]);
    Passport::actingAs($user, ['mcp:use']);
    $call = mcpCall('tools/list');

    $this->postJson('/mcp', $call['json'], $call['headers'])->assertForbidden();
});

it('only revokes the own connections', function () {
    $client = Client::query()->forceCreate(['name' => 'Fremd', 'secret' => null, 'provider' => null, 'redirect_uris' => ['https://claude.ai/cb'], 'grant_types' => ['authorization_code'], 'revoked' => false]);

    $this->actingAs(member())->delete(route('mcp.connections.destroy', $client->getKey()))->assertNotFound();
});
