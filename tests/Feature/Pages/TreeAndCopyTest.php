<?php

use App\Modules\Pages\Models\Attachment;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\AttachmentService;
use App\Modules\Pages\Services\PageService;
use App\Modules\Spaces\Enums\SpaceRole;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->editor = member();
    $this->space = space([[$this->editor, SpaceRole::Editor]]);
});

function order(int $spaceId, ?int $parentId = null): array
{
    return Page::query()->where('space_id', $spaceId)->where('parent_id', $parentId)
        ->orderBy('position')->pluck('title')->all();
}

it('reorders pages before and after siblings', function () {
    $a = page($this->space, ['title' => 'A']);
    page($this->space, ['title' => 'B']);
    $c = page($this->space, ['title' => 'C']);

    $this->actingAs($this->editor)->postJson(route('pages.place', $c), ['target_id' => $a->id, 'placement' => 'before'])->assertOk();
    expect(order($this->space->id))->toBe(['C', 'A', 'B']);

    $this->actingAs($this->editor)->postJson(route('pages.place', $c), ['target_id' => $a->id, 'placement' => 'after'])->assertOk();
    expect(order($this->space->id))->toBe(['A', 'C', 'B']);
});

it('makes a page a sub-page when dropped onto another page', function () {
    $a = page($this->space, ['title' => 'A']);
    $child = page($this->space, ['title' => 'Kind'], $a);
    $b = page($this->space, ['title' => 'B']);

    $this->actingAs($this->editor)->postJson(route('pages.place', $b), ['target_id' => $a->id, 'placement' => 'inside'])
        ->assertOk()->assertJson(['parent_id' => $a->id]);

    expect(order($this->space->id, $a->id))->toBe(['Kind', 'B']);
    expect(order($this->space->id))->toBe(['A']);

    // and out again, before the former parent
    $this->actingAs($this->editor)->postJson(route('pages.place', $child), ['target_id' => $a->id, 'placement' => 'before'])->assertOk();
    expect(order($this->space->id))->toBe(['Kind', 'A']);
});

it('never drops a page into itself or its sub-pages', function () {
    $a = page($this->space, ['title' => 'A']);
    $b = page($this->space, ['title' => 'B'], $a);

    $this->actingAs($this->editor)->postJson(route('pages.place', $a), ['target_id' => $b->id, 'placement' => 'inside'])->assertUnprocessable();
    $this->actingAs($this->editor)->postJson(route('pages.place', $a), ['target_id' => $a->id, 'placement' => 'inside'])->assertUnprocessable();
    $this->actingAs($this->editor)->postJson(route('pages.place', $a), ['target_id' => $b->id, 'placement' => 'before'])->assertUnprocessable();

    expect($a->fresh()->parent_id)->toBeNull();
});

it('rejects drag and drop without editing rights or across spaces', function () {
    $reader = member();
    $space = space([[$reader, SpaceRole::Reader]]);
    $a = page($space, ['title' => 'A']);
    $b = page($space, ['title' => 'B']);
    $foreign = page($this->space, ['title' => 'Fremd']);

    $this->actingAs($reader)->postJson(route('pages.place', $a), ['target_id' => $b->id, 'placement' => 'inside'])->assertForbidden();
    $admin = admin();
    $this->actingAs($admin)->postJson(route('pages.place', $a), ['target_id' => $foreign->id, 'placement' => 'inside'])->assertNotFound();
    $this->actingAs($admin)->postJson(route('pages.place', $a), ['target_id' => $b->id, 'placement' => 'sideways'])->assertUnprocessable();
});

it('duplicates a page with sub-pages, labels and attachments right after the original', function () {
    $original = page($this->space, ['title' => 'Handbuch', 'labels' => ['wichtig']]);
    $attachment = app(AttachmentService::class)->storeContents($original, 'plan.txt', 'Inhalt', $this->editor);
    app(PageService::class)->update($original, 'Handbuch', "Siehe [Plan](/attachments/{$attachment->id})", $this->editor);
    page($this->space, ['title' => 'Kapitel'], $original);
    page($this->space, ['title' => 'Danach']);

    $this->actingAs($this->editor)->post(route('pages.duplicate', $original))->assertRedirect();

    $copyTitle = __(':title (copy)', ['title' => 'Handbuch']);
    expect(order($this->space->id))->toBe(['Handbuch', $copyTitle, 'Danach']);
    $copy = Page::query()->where('title', $copyTitle)->sole();
    expect($copy->labels->pluck('name')->all())->toBe(['wichtig']);
    expect(order($this->space->id, $copy->id))->toBe(['Kapitel']);

    $copiedAttachment = Attachment::query()->where('page_id', $copy->id)->sole();
    expect($copiedAttachment->id)->not->toBe($attachment->id);
    expect($copy->content)->toContain("/attachments/{$copiedAttachment->id}");
    Storage::disk('local')->assertExists($copiedAttachment->path);
});

it('copies a page into another space the user may edit', function () {
    $target = space([[$this->editor, SpaceRole::Editor]], ['name' => 'Ziel']);
    $readOnly = space([[$this->editor, SpaceRole::Reader]], ['name' => 'Nur lesen']);
    $page = page($this->space, ['title' => 'Vorlage']);

    $this->actingAs($this->editor)->get(route('pages.copy', $page))->assertOk()
        ->assertSee('<optgroup label="Ziel">', false)
        ->assertDontSee('<optgroup label="Nur lesen">', false);

    $this->actingAs($this->editor)->post(route('pages.copy.store', $page), ['target' => "space:{$readOnly->id}"])->assertForbidden();
    $this->actingAs($this->editor)->post(route('pages.copy.store', $page), ['target' => "space:{$target->id}"])->assertRedirect();

    expect(Page::query()->where('space_id', $target->id)->where('title', 'Vorlage')->exists())->toBeTrue();
});

it('toggles favorites and lists them on the home page', function () {
    $page = page($this->space, ['title' => 'Lieblingsseite']);

    $this->actingAs($this->editor)->postJson(route('pages.favorite', $page))->assertJson(['favorite' => true]);
    $this->actingAs($this->editor)->get('/')->assertSee(__('Favorites'))->assertSee('Lieblingsseite');
    $this->actingAs($this->editor)->get($page->url())->assertSee(__('Remove from favorites'));

    $this->actingAs($this->editor)->postJson(route('pages.favorite', $page))->assertJson(['favorite' => false]);
    $this->actingAs(member())->postJson(route('pages.favorite', $page))->assertForbidden();
});

it('shows pages created by the user', function () {
    page($this->space, ['title' => 'Meine Seite'], null, $this->editor);

    $this->actingAs($this->editor)->get(route('spaces.show', $this->space))->assertSee(__('Created by me'))->assertSee('Meine Seite');
});
