<?php

use App\Modules\Groups\Models\Group;
use App\Modules\Groups\Services\GroupService;
use App\Modules\Spaces\Enums\SpaceRole;

/**
 * Rights per role: [route, expected status for none, reader, editor, space admin, workspace admin]
 */
dataset('page actions', [
    'view space' => [fn ($s, $p) => ['get', route('spaces.show', $s)], [403, 200, 200, 200, 200]],
    'view page' => [fn ($s, $p) => ['get', route('pages.show', $p)], [403, 200, 200, 200, 200]],
    'version history' => [fn ($s, $p) => ['get', route('pages.versions', $p)], [403, 200, 200, 200, 200]],
    'export page' => [fn ($s, $p) => ['get', route('export.page', [$p, 'page-md'])], [403, 200, 200, 200, 200]],
    'export space' => [fn ($s, $p) => ['get', route('export.space', [$s, 'space-zip'])], [403, 200, 200, 200, 200]],
    'create form' => [fn ($s, $p) => ['get', route('pages.create', $s)], [403, 403, 200, 200, 200]],
    'edit form' => [fn ($s, $p) => ['get', route('pages.edit', $p)], [403, 403, 200, 200, 200]],
    'import form' => [fn ($s, $p) => ['get', route('import.create', $s)], [403, 403, 200, 200, 200]],
    'move form' => [fn ($s, $p) => ['get', route('pages.move', $p)], [403, 403, 200, 200, 200]],
    'trash' => [fn ($s, $p) => ['get', route('trash.index', $s)], [403, 403, 403, 200, 200]],
    'delete page' => [fn ($s, $p) => ['delete', route('pages.destroy', $p)], [403, 403, 403, 302, 302]],
]);

it('enforces the rights of each role', function (Closure $request, array $expected) {
    $users = [
        'none' => member(),
        'reader' => member(),
        'editor' => member(),
        'space admin' => member(),
        'workspace admin' => admin(),
    ];

    foreach (array_keys($users) as $index => $role) {
        $space = space([
            [$users['reader'], SpaceRole::Reader],
            [$users['editor'], SpaceRole::Editor],
            [$users['space admin'], SpaceRole::Admin],
        ]);
        $page = page($space, ['title' => 'Testseite']);

        [$method, $url] = $request($space, $page);
        $this->actingAs($users[$role])->{$method}($url)->assertStatus($expected[$index]);
    }
})->with('page actions');

it('grants access through groups, the highest role wins', function () {
    $user = member();
    $group = app(GroupService::class)->create(['name' => 'Redaktion'], [$user->id]);
    $space = space([[$group, SpaceRole::Editor], [$user, SpaceRole::Reader]]);

    $this->actingAs($user)->get(route('pages.create', $space))->assertOk();
});

it('grants access to everyone through the Everyone group', function () {
    $space = space([[Group::everyone(), SpaceRole::Reader]]);
    $page = page($space);

    $this->actingAs(member())->get(route('pages.show', $page))->assertOk();
    $this->actingAs(member())->get(route('pages.edit', $page))->assertForbidden();
});

it('lists only readable spaces', function () {
    $user = member();
    $visible = space([[$user, SpaceRole::Reader]], ['name' => 'Sichtbar']);
    space([], ['name' => 'Geheim']);

    $this->actingAs($user)->get(route('spaces.index'))
        ->assertOk()
        ->assertSee('Sichtbar')
        ->assertDontSee('Geheim');
});

it('cannot create pages under a parent from another space', function () {
    $editor = member();
    $space = space([[$editor, SpaceRole::Editor]]);
    $foreignPage = page(space());

    $this->actingAs($editor)
        ->post(route('pages.store', $space), ['title' => 'X', 'parent_id' => $foreignPage->id])
        ->assertNotFound();
});

it('does not allow editing via a direct request without rights', function () {
    $reader = member();
    $space = space([[$reader, SpaceRole::Reader]]);
    $page = page($space, ['title' => 'Original']);

    $this->actingAs($reader)
        ->put(route('pages.update', $page), ['title' => 'Gehackt', 'content' => '', 'version' => 1])
        ->assertForbidden();

    expect($page->fresh()->title)->toBe('Original');
});
