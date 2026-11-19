<?php

use App\Modules\Spaces\Enums\SpaceRole;

/*
 * Every main page renders for admins and members – also in the empty state
 * (no spaces yet), where extra links such as "Create the first space" appear.
 */
dataset('main pages', ['/', '/spaces', '/search', '/search?q=test', '/account']);

it('renders the main pages for an admin without any space', function (string $url) {
    $this->actingAs(admin())->get($url)->assertOk();
})->with('main pages');

it('renders the main pages for a member without any space', function (string $url) {
    $this->actingAs(member())->get($url)->assertOk();
})->with('main pages');

it('renders the main pages with spaces and pages', function (string $url) {
    $member = member();
    $space = space([[$member, SpaceRole::Editor]]);
    page($space, ['title' => 'Startseite des Bereichs']);

    $this->actingAs($member)->get($url)->assertOk();
    $this->actingAs(admin())->get($url)->assertOk();
})->with('main pages');

it('renders every page view of a space', function () {
    $admin = admin();
    $space = space();
    $parent = page($space, ['title' => 'Eltern']);
    $page = page($space, ['title' => 'Kind', 'content' => "# Titel\n\nText"], $parent);

    foreach ([
        route('spaces.show', $space), route('pages.create', $space), route('trash.index', $space), route('import.create', $space),
        $page->url(), route('pages.edit', $page), route('pages.move', $page), route('pages.copy', $page),
        route('pages.versions', $page), route('pages.versions.show', [$page, 1]),
    ] as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
    }
});
