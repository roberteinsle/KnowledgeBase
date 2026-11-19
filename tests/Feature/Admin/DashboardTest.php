<?php

use App\Core\Admin\HealthCheckRegistry;
use App\Filament\Widgets\SystemStatusWidget;
use App\Modules\Spaces\Enums\SpaceRole;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

it('shows key figures of all modules on the admin dashboard', function () {
    $admin = admin();
    $space = space([], ['name' => 'Handbuch']);
    page($space);
    page($space);

    $this->actingAs($admin)->get('/admin')->assertOk()
        ->assertSee([__('Users'), __('Groups'), __('Spaces'), __('Pages'), __('Attachments'), __('Labels'), __('Sign-ins (7 days)'), __('Connected AI apps')])
        ->assertSee(__('System status'))
        ->assertSee([__('Database'), __('File storage'), __('Application'), __('E-mail (SMTP)'), __('MCP server')])
        ->assertSee(__('Activity in the last 30 days'));
});

it('keeps the dashboard widgets away from members', function () {
    $this->actingAs(member())->get('/admin')->assertForbidden();
});

it('reports the mail setup and tests it on demand', function () {
    config(['mail.default' => 'log']);
    $results = collect(app(HealthCheckRegistry::class)->run())->keyBy(fn ($r) => $r['check']->key());
    expect($results['mail']['result']->status)->toBe('warning');

    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '']);
    $results = collect(app(HealthCheckRegistry::class)->run())->keyBy(fn ($r) => $r['check']->key());
    expect($results['mail']['result']->status)->toBe('error');
});

it('sends a test e-mail to the admin from the status widget', function () {
    Mail::fake();
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'mail.example.com']);
    $admin = admin();
    $this->actingAs($admin);

    Livewire::test(SystemStatusWidget::class)->callAction('sendTestMail')->assertNotified();

    expect(collect(app(HealthCheckRegistry::class)->run())->keyBy(fn ($r) => $r['check']->key())['mail']['result']->message)
        ->toContain($admin->email);
});

it('tests the SMTP connection and reports failures without credentials', function () {
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.password' => 'geheim123']);
    $this->actingAs(admin());

    Livewire::test(SystemStatusWidget::class)->callAction('testSmtp')->assertNotified();

    $status = collect(app(HealthCheckRegistry::class)->run())->keyBy(fn ($r) => $r['check']->key())['mail']['result'];
    expect($status->status)->toBe('error')->and($status->message)->not->toContain('geheim123');
});

it('reports missing OAuth keys for the MCP server', function () {
    config(['passport.private_key' => null, 'passport.public_key' => null]);
    $private = storage_path('oauth-private.key');
    $backup = $private.'.bak';
    rename($private, $backup);

    try {
        $mcp = collect(app(HealthCheckRegistry::class)->run())->keyBy(fn ($r) => $r['check']->key())['mcp']['result'];
        expect($mcp->status)->toBe('error');
    } finally {
        rename($backup, $private);
    }
});

it('serves a plain health check without external resources', function () {
    $this->get('/up')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->assertSeeText('OK');
});

it('moves the Livewire endpoints below /admin and closes the default ones', function () {
    expect(route('livewire.update', absolute: false))->toBe('/admin/livewire/update');
    expect(route('livewire.upload-file', absolute: false))->toBe('/admin/livewire/upload-file');
    expect(route('livewire.preview-file', ['filename' => 'x'], false))->toBe('/admin/livewire/preview-file/x');

    $prefix = EndpointResolver::prefix();
    $this->actingAs(admin())->post($prefix.'/update')->assertNotFound();
    $this->post($prefix.'/upload-file')->assertNotFound();
    $this->get($prefix.'/preview-file/x')->assertNotFound();
});

it('saves inline edits as JSON and refuses stale versions', function () {
    $editor = member();
    $page = page(space([[$editor, SpaceRole::Editor]]), ['title' => 'Inline', 'content' => 'alt', 'labels' => ['a']]);

    $this->actingAs($editor)->putJson(route('pages.update', $page), ['title' => 'Inline', 'content' => "## Neu\n\n> Zitat", 'labels' => 'a', 'version' => 1])
        ->assertOk()->assertJson(['version' => 2, 'url' => $page->fresh()->url()]);

    $this->actingAs($editor)->putJson(route('pages.update', $page), ['title' => 'Inline', 'content' => 'veraltet', 'labels' => 'a', 'version' => 1])
        ->assertStatus(409);

    expect($page->fresh()->content)->toBe("## Neu\n\n> Zitat");
});

it('offers inline editing only to editors', function () {
    $editor = member();
    $reader = member();
    $page = page(space([[$editor, SpaceRole::Editor], [$reader, SpaceRole::Reader]]));

    $this->actingAs($editor)->get($page->url())->assertSee('data-inline-edit', false)->assertSee('data-slash-labels', false);
    $this->actingAs($reader)->get($page->url())->assertDontSee('data-inline-edit', false);
});
