<?php

use App\Modules\Pages\Services\AttachmentService;
use App\Modules\Pages\Services\PageService;
use App\Modules\Spaces\Enums\SpaceRole;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->user = member();
    $this->space = space([[$this->user, SpaceRole::Reader]]);
});

it('finds pages by title and content with prefix matching', function () {
    page($this->space, ['title' => 'Urlaubsantrag', 'content' => 'So beantragst du deinen Urlaub im Personalportal.']);
    page($this->space, ['title' => 'Kantine', 'content' => 'Speiseplan']);

    $this->actingAs($this->user)->get('/suche?q=personalport')
        ->assertOk()->assertSee('Urlaubsantrag')->assertDontSee('Kantine');
});

it('highlights hits without allowing HTML', function () {
    page($this->space, ['title' => 'Hinweis', 'content' => 'Achtung <b>wichtig</b> Sicherheit']);

    $this->actingAs($this->user)->get('/suche?q=sicherheit')
        ->assertSee('<mark class="bg-yellow-100 px-0.5">Sicherheit</mark>', false)
        ->assertDontSee('<b>wichtig</b>', false);
});

it('never shows results from spaces the user cannot read', function () {
    page(space(), ['title' => 'Gehaltsliste', 'content' => 'vertraulich']);

    $this->actingAs($this->user)->get('/suche?q=vertraulich')->assertOk()->assertDontSee('Gehaltsliste');
});

it('finds attachments by name and text content', function () {
    $page = page($this->space, ['title' => 'Formulare']);
    app(AttachmentService::class)->storeContents($page, 'reisekosten.txt', 'Kilometerpauschale 30 Cent', admin());

    $this->actingAs($this->user)->get('/suche?q=kilometerpauschale')->assertOk()->assertSee('reisekosten.txt');
});

it('does not show trashed pages', function () {
    $page = page($this->space, ['title' => 'Veraltet', 'content' => 'archivwort']);
    app(PageService::class)->trash($page, admin());

    $this->actingAs($this->user)->get('/suche?q=archivwort')->assertDontSee('Veraltet');
});

it('handles SQL injection and tsquery syntax safely', function (string $payload) {
    page($this->space, ['title' => 'Normal']);

    $response = $this->actingAs($this->user)->get('/suche?q='.urlencode($payload));

    expect($response->status())->toBe(200);
    expect($response->getContent())->not->toContain('SQLSTATE');
    expect(Schema::hasTable('users'))->toBeTrue();
})->with([...sqlInjectionPayloads(), 'a & | ! ( ) :*', '\\', "''''", str_repeat('x', 200)]);

it('rejects overly long queries', function () {
    $this->actingAs($this->user)->get('/suche?q='.str_repeat('x', 201))->assertSessionHasErrors('q');
});
