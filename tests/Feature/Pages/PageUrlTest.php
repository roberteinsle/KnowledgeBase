<?php

use App\Modules\Pages\Services\PageService;
use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Models\Space;
use App\Modules\Spaces\Services\SpaceService;

beforeEach(function () {
    $this->user = member();
    $this->space = space([[$this->user, SpaceRole::Editor]], ['name' => 'Handbuch']);
});

it('uses readable URLs /<space>/<title>-<id>', function () {
    $page = page($this->space, ['title' => 'Grüße & Überstunden: FAQ']);

    expect($page->url())->toEndWith('/handbuch/gruesse-ueberstunden-faq-'.$page->id);
    $this->actingAs($this->user)->get($page->url())->assertOk()->assertSee('Grüße &amp; Überstunden: FAQ', false);
});

it('forwards the permanent link /pages/<id> to the readable URL', function () {
    $page = page($this->space, ['title' => 'Urlaub']);

    $this->actingAs($this->user)->get("/pages/{$page->id}")->assertRedirect($page->url());
});

it('corrects outdated titles and spaces with a permanent redirect', function () {
    $page = page($this->space, ['title' => 'Alter Titel']);
    $old = $page->url();
    app(PageService::class)->update($page, 'Neuer Titel', '', admin());
    $page->refresh();

    $this->actingAs($this->user)->get($old)->assertStatus(301)->assertRedirect($page->url());
    $this->actingAs($this->user)->get("/anderer-bereich/neuer-titel-{$page->id}")->assertStatus(301)->assertRedirect($page->url());
    $this->actingAs($this->user)->get("/handbuch/{$page->id}")->assertStatus(301)->assertRedirect($page->url());
});

it('still checks the rights on readable URLs', function () {
    $page = page(space([], ['name' => 'Geheim']), ['title' => 'Intern']);

    $this->actingAs($this->user)->get($page->url())->assertForbidden();
    $this->actingAs($this->user)->get('/geheim/irgendwas-999999')->assertNotFound();
});

it('keeps system paths working and never gives a space a reserved slug', function () {
    $space = app(SpaceService::class)->create(['name' => 'Search'], admin());

    expect($space->slug)->toBe('search-space');
    expect(Space::RESERVED_SLUGS)->toContain('admin', 'search', 'login', 'spaces', 'pages');
    $this->actingAs($this->user)->get('/search?q=x')->assertOk();
    $this->actingAs($this->user)->get('/spaces')->assertOk();
});

it('falls back to the id for titles without letters', function () {
    $page = page($this->space, ['title' => '😀']);

    expect($page->url())->toEndWith('/handbuch/'.$page->id);
    $this->actingAs($this->user)->get($page->url())->assertOk();
});

it('links the tree, breadcrumbs and search results with readable URLs', function () {
    $parent = page($this->space, ['title' => 'Eltern', 'content' => 'suchwort']);
    $child = page($this->space, ['title' => 'Kind'], $parent);

    $this->actingAs($this->user)->get($child->url())
        ->assertSee('href="'.$parent->url().'"', false)
        ->assertSee('data-url="'.$child->url().'"', false);
    $this->actingAs($this->user)->get('/search?q=suchwort')->assertSee('href="'.$parent->url().'"', false);
});
