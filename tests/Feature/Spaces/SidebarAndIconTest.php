<?php

use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Services\SpaceService;
use Illuminate\Support\Facades\Storage;

const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

beforeEach(fn () => Storage::fake('local'));

it('shows the space switcher with readable spaces only and the page tree', function () {
    $user = member();
    $mine = space([[$user, SpaceRole::Reader]], ['name' => 'Homelab']);
    space([[$user, SpaceRole::Reader]], ['name' => 'Archiv']);
    space([], ['name' => 'Geheimbereich']);
    page($mine, ['title' => 'Netzwerkplan']);

    $this->actingAs($user)->get(route('spaces.show', $mine))
        ->assertOk()
        ->assertSee(__('Search spaces'))
        ->assertSee('Archiv')
        ->assertDontSee('Geheimbereich')
        ->assertSee('Netzwerkplan')
        ->assertSee(__('Overview'))
        ->assertSee('/search?space='.$mine->slug, false);
});

it('lists all spaces instead of a page tree outside a space', function () {
    $user = member();
    space([[$user, SpaceRole::Reader]], ['name' => 'Zebra']);
    $second = space([[$user, SpaceRole::Reader]], ['name' => 'alpha']);
    page($second, ['title' => 'Seite im Bereich']);

    // Visiting a space must not make it "stick" on pages outside a space.
    $this->actingAs($user)->get(route('spaces.show', $second))->assertSee('Seite im Bereich');

    foreach (['/', '/spaces', '/account'] as $url) {
        $this->actingAs($user)->get($url)
            ->assertDontSee('data-page-tree', false)
            ->assertSeeInOrder(['alpha', 'Zebra']);
    }
});

it('shows editing actions in the tree only to editors', function () {
    $reader = member();
    $editor = member();
    $space = space([[$reader, SpaceRole::Reader], [$editor, SpaceRole::Editor]]);
    $page = page($space);

    $this->actingAs($reader)->get($page->url())
        ->assertDontSee('data-place-url', false)
        ->assertDontSee(route('pages.duplicate', '__PAGE__'), false)
        ->assertSee(__('Copy link'));

    $this->actingAs($editor)->get($page->url())
        ->assertSee('data-place-url', false)
        ->assertSee(route('pages.duplicate', '__PAGE__'), false)
        ->assertDontSee(__('Move to trash'));
});

it('shows the space settings link to workspace admins only', function () {
    $member = member();
    $space = space([[$member, SpaceRole::Admin]]);

    $this->actingAs($member)->get(route('spaces.show', $space))->assertDontSee(__('Space settings'));
    $this->actingAs(admin())->get(route('spaces.show', $space))->assertSee(__('Space settings'));
});

it('serves the space icon only to users who can read the space', function () {
    $user = member();
    Storage::disk('local')->put('space-icons/a.png', base64_decode(PNG));
    $space = space([[$user, SpaceRole::Reader]], ['icon_path' => 'space-icons/a.png']);

    $this->actingAs($user)->get(route('spaces.icon', $space))
        ->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs(member())->get(route('spaces.icon', $space))->assertForbidden();
    $this->actingAs($user)->get(route('spaces.show', $space))->assertSee(route('spaces.icon', $space), false);
});

it('never delivers a non-raster file as space icon', function () {
    $user = member();
    Storage::disk('local')->put('space-icons/x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
    $space = space([[$user, SpaceRole::Reader]], ['name' => 'Gefährlich', 'icon_path' => 'space-icons/x.svg']);

    // Falls back to the generated avatar instead of the uploaded content.
    $response = $this->actingAs($user)->get(route('spaces.icon', $space))->assertOk();
    expect($response->getContent())->not->toContain('<script')->toContain('>GE<');
});

it('removes the old icon file when it is replaced or the space is deleted', function () {
    Storage::disk('local')->put('space-icons/old.png', base64_decode(PNG));
    Storage::disk('local')->put('space-icons/new.png', base64_decode(PNG));
    $space = space([], ['icon_path' => 'space-icons/old.png']);
    $service = app(SpaceService::class);

    $service->update($space, ['name' => $space->name, 'icon_path' => 'space-icons/new.png']);
    Storage::disk('local')->assertMissing('space-icons/old.png');

    $service->delete($space->fresh());
    Storage::disk('local')->assertMissing('space-icons/new.png');
});

it('generates an avatar from the emoji or the initials of the name', function () {
    $user = member();
    $emoji = space([[$user, SpaceRole::Reader]], ['name' => 'Mit Emoji', 'icon' => '🚀']);
    $plain = space([[$user, SpaceRole::Reader]], ['name' => 'Human Resources']);

    $this->actingAs($user)->get(route('spaces.icon', $emoji))
        ->assertOk()->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8')->assertSee('🚀');
    $this->actingAs($user)->get(route('spaces.icon', $plain))->assertOk()->assertSee('>HR<', false);
    $this->actingAs($user)->get(route('spaces.show', $plain))->assertSee(route('spaces.icon', $plain), false);
});

it('restricts the search to one space', function () {
    $user = member();
    $a = space([[$user, SpaceRole::Reader]], ['name' => 'Bereich A']);
    $b = space([[$user, SpaceRole::Reader]], ['name' => 'Bereich B']);
    page($a, ['title' => 'Treffer A', 'content' => 'gemeinsamesWort']);
    page($b, ['title' => 'Treffer B', 'content' => 'gemeinsamesWort']);
    $result = 'hover:underline">';

    $this->actingAs($user)->get('/search?q=gemeinsameswort&space='.$a->slug)
        ->assertSee($result.'Treffer A', false)->assertDontSee($result.'Treffer B', false)
        ->assertSee(__('Only in :space', ['space' => 'Bereich A']));

    // A space without access is ignored instead of leaking anything.
    $hidden = space([], ['name' => 'Versteckt']);
    $this->actingAs($user)->get('/search?q=gemeinsameswort&space='.$hidden->slug)
        ->assertSee($result.'Treffer A', false)->assertSee($result.'Treffer B', false);
});
