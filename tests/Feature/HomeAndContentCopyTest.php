<?php

use App\Modules\Spaces\Enums\SpaceRole;

it('shows at most six spaces on the home page, sorted A–Z, the rest behind "Show all"', function () {
    $user = member();
    foreach (['Golf', 'alpha', 'Echo', 'Bravo', 'hotel', 'Charlie', 'Delta', 'Foxtrot'] as $name) {
        space([[$user, SpaceRole::Reader]], ['name' => $name]);
    }

    $html = $this->actingAs($user)->get('/')->assertOk()
        ->assertSee(__('Show all spaces (:count)', ['count' => 8]))
        ->getContent();

    // Only the widget (the sidebar lists spaces as well): case-insensitive A–Z,
    // the 7th and 8th entry are hidden until "Show all".
    $widget = substr($html, strpos($html, __('Your spaces')));
    preg_match_all('/<span class="block font-medium text-slate-900">([^<]+)<\/span>/', $widget, $names);
    expect($names[1])->toBe(['alpha', 'Bravo', 'Charlie', 'Delta', 'Echo', 'Foxtrot', 'Golf', 'hotel']);
    expect(substr_count($widget, 'x-show="all" x-cloak'))->toBe(2);
});

it('lists all spaces without limit on the spaces page', function () {
    $user = member();
    foreach (range(1, 8) as $i) {
        space([[$user, SpaceRole::Reader]], ['name' => "Bereich {$i}"]);
    }

    $this->actingAs($user)->get('/spaces')->assertOk()->assertDontSee(__('Show all spaces (:count)', ['count' => 8]));
});

it('returns the raw Markdown for "Copy page content" only to readers', function () {
    $user = member();
    $page = page(space([[$user, SpaceRole::Reader]]), ['content' => "## Titel\n\n<b>roh</b>"]);

    $this->actingAs($user)->get(route('pages.markdown', $page))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSeeText("## Titel\n\n<b>roh</b>", false);
    $this->actingAs(member())->get(route('pages.markdown', $page))->assertForbidden();

    $this->actingAs($user)->get($page->url())
        ->assertSee(__('Copy page content'))
        ->assertSee('data-copy-fetch="'.route('pages.markdown', '__PAGE__').'"', false);
});

it('uses the logo as favicon in the app, on sign-in and in the admin area', function () {
    $this->get('/login')->assertSee('rel="icon" href="'.asset('favicon.svg').'"', false);
    $this->actingAs(member())->get('/')->assertSee('rel="icon" href="'.asset('favicon.svg').'"', false);
    $this->actingAs(admin())->get('/admin')->assertSee(asset('favicon.svg'), false);
    expect(file_get_contents(public_path('favicon.svg')))->toBe(file_get_contents(base_path('docs/logo.svg')));
});
