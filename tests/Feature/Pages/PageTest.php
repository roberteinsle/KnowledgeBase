<?php

use App\Core\Activity\Activity;
use App\Modules\Pages\Models\Page;
use App\Modules\Spaces\Enums\SpaceRole;

beforeEach(function () {
    $this->editor = member();
    $this->space = space([[$this->editor, SpaceRole::Admin]]);
});

it('creates a page with labels and a first version', function () {
    $this->actingAs($this->editor)
        ->post(route('pages.store', $this->space), [
            'title' => '  Onboarding   Leitfaden ',
            'content' => "## Erste Schritte\r\n\r\nWillkommen!",
            'labels' => 'Onboarding, HR Prozesse, onboarding',
        ])
        ->assertRedirect();

    $page = Page::query()->sole();
    expect($page->title)->toBe('Onboarding Leitfaden');
    expect($page->content)->toBe("## Erste Schritte\n\nWillkommen!");
    expect($page->labels->pluck('name')->all())->toBe(['hr-prozesse', 'onboarding']);
    expect($page->versions()->count())->toBe(1);
});

it('validates input on the server', function () {
    $this->actingAs($this->editor)
        ->post(route('pages.store', $this->space), ['title' => str_repeat('a', 256)])
        ->assertSessionHasErrors('title');

    $this->actingAs($this->editor)
        ->post(route('pages.store', $this->space), ['title' => ''])
        ->assertSessionHasErrors('title');
});

it('creates a new version on every change, not on unchanged saves', function () {
    $page = page($this->space, ['title' => 'A', 'content' => 'eins']);

    $this->actingAs($this->editor)->put(route('pages.update', $page), ['title' => 'A', 'content' => 'zwei', 'version' => 1]);
    $this->actingAs($this->editor)->put(route('pages.update', $page), ['title' => 'A', 'content' => 'zwei', 'version' => 2]);

    expect($page->fresh()->version)->toBe(2);
    expect($page->versions()->count())->toBe(2);
});

it('refuses to overwrite a newer version', function () {
    $page = page($this->space, ['title' => 'A', 'content' => 'eins']);
    $this->actingAs($this->editor)->put(route('pages.update', $page), ['title' => 'A', 'content' => 'zwei', 'version' => 1]);

    $this->actingAs($this->editor)
        ->put(route('pages.update', $page), ['title' => 'A', 'content' => 'veraltet', 'version' => 1])
        ->assertSessionHasErrors('content');

    expect($page->fresh()->content)->toBe('zwei');
});

it('restores an old version as a new version', function () {
    $page = page($this->space, ['title' => 'A', 'content' => 'eins']);
    $this->actingAs($this->editor)->put(route('pages.update', $page), ['title' => 'B', 'content' => 'zwei', 'version' => 1]);

    $this->actingAs($this->editor)->post(route('pages.versions.restore', [$page, 1]))->assertRedirect($page->url());

    $page->refresh();
    expect([$page->title, $page->content, $page->version])->toBe(['A', 'eins', 3]);
});

it('shows a diff between versions', function () {
    $page = page($this->space, ['title' => 'A', 'content' => 'Zeile alt']);
    $this->actingAs($this->editor)->put(route('pages.update', $page), ['title' => 'A', 'content' => 'Zeile neu', 'version' => 1]);

    $this->actingAs($this->editor)->get(route('pages.versions.show', [$page, 2]))
        ->assertOk()->assertSee('Zeile alt')->assertSee('Zeile neu');
});

it('nests sub-pages and shows breadcrumbs', function () {
    $parent = page($this->space, ['title' => 'Eltern']);
    $child = page($this->space, ['title' => 'Kind'], $parent);

    $this->actingAs($this->editor)->get($child->url())->assertOk()->assertSeeInOrder(['Eltern', 'Kind']);
    $this->actingAs($this->editor)->get($parent->url())->assertSee(__('Sub-pages'));
});

it('moves pages but never below themselves', function () {
    $a = page($this->space, ['title' => 'A']);
    $b = page($this->space, ['title' => 'B'], $a);
    $c = page($this->space, ['title' => 'C']);

    $this->actingAs($this->editor)->put(route('pages.move.update', $a), ['parent_id' => $b->id])->assertSessionHasErrors('parent_id');
    $this->actingAs($this->editor)->put(route('pages.move.update', $a), ['parent_id' => $a->id])->assertSessionHasErrors('parent_id');

    $this->actingAs($this->editor)->put(route('pages.move.update', $a), ['parent_id' => $c->id])->assertRedirect();
    expect($a->fresh()->parent_id)->toBe($c->id);

    $this->actingAs($this->editor)->put(route('pages.move.update', $a), ['parent_id' => null])->assertRedirect();
    expect($a->fresh()->parent_id)->toBeNull();
});

it('moves pages with sub-pages to the trash and restores them together', function () {
    $parent = page($this->space, ['title' => 'Eltern']);
    $child = page($this->space, ['title' => 'Kind'], $parent);

    $this->actingAs($this->editor)->delete(route('pages.destroy', $parent))->assertRedirect(route('spaces.show', $this->space));
    expect(Page::count())->toBe(0);
    $this->actingAs($this->editor)->get($child->url())->assertNotFound();

    $this->actingAs($this->editor)->get(route('trash.index', $this->space))->assertSee('Eltern');
    $this->actingAs($this->editor)->post(route('trash.restore', $parent))->assertRedirect($parent->url());
    expect(Page::count())->toBe(2);
});

it('deletes pages permanently only from the trash', function () {
    $page = page($this->space);

    $this->actingAs($this->editor)->delete(route('trash.destroy', $page))->assertForbidden();

    $this->actingAs($this->editor)->delete(route('pages.destroy', $page));
    $this->actingAs($this->editor)->delete(route('trash.destroy', $page))->assertRedirect();
    expect(Page::withTrashed()->count())->toBe(0);
});

it('escapes HTML and drops unsafe links when rendering Markdown', function () {
    $page = page($this->space, ['content' => "<script>alert(1)</script>\n\n[klick](javascript:alert(1))\n\n<img src=x onerror=alert(1)>\n\n**fett**"]);

    $response = $this->actingAs($this->editor)->get($page->url())->assertOk();

    $html = $response->getContent();
    expect($html)->not->toContain('<script>alert(1)</script>')
        ->not->toContain('href="javascript:')
        ->not->toContain('<img src=x onerror')
        ->toContain('&lt;script&gt;')
        ->toContain('<strong>fett</strong>');
});

it('escapes titles and labels', function () {
    $page = page($this->space, ['title' => '<b>Titel</b>']);

    $this->actingAs($this->editor)->get($page->url())
        ->assertDontSee('<b>Titel</b>', false)
        ->assertSee('&lt;b&gt;Titel&lt;/b&gt;', false);
});

it('lists pages by label within readable spaces only', function () {
    page($this->space, ['title' => 'Sichtbar', 'labels' => ['wichtig']]);
    page(space(), ['title' => 'Geheim', 'labels' => ['wichtig']]);

    $this->actingAs($this->editor)->get(route('labels.show', 'wichtig'))
        ->assertOk()->assertSee('Sichtbar')->assertDontSee('Geheim');
});

it('writes changes to the activity log without the content', function () {
    $page = page($this->space, ['title' => 'Protokolliert', 'content' => 'Geheimer Inhalt']);

    $log = Activity::query()->where('action', 'page.created')->sole();
    expect($log->subject_id)->toBe($page->id);
    expect(json_encode($log->properties))->not->toContain('Geheimer Inhalt');
});

it('shows the home page with spaces and recent pages', function () {
    page($this->space, ['title' => 'Neu bearbeitet']);

    $this->actingAs($this->editor)->get('/')->assertOk()->assertSee($this->space->name)->assertSee('Neu bearbeitet');
});
