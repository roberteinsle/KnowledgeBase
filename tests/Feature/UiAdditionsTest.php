<?php

use App\Modules\Spaces\Enums\SpaceRole;

it('links back to the knowledge base in the admin navigation', function () {
    $this->actingAs(admin())->get('/admin')->assertOk()
        ->assertSee(__('Back to the knowledge base'))
        ->assertSee('href="'.route('home').'"', false);
});

it('offers "Import Markdown" next to "New page" in a space for editors', function () {
    $editor = member();
    $reader = member();
    $space = space([[$editor, SpaceRole::Editor], [$reader, SpaceRole::Reader]]);
    page($space);

    $this->actingAs($editor)->get(route('spaces.show', $space))
        ->assertSeeInOrder([route('import.create', $space), route('pages.create', $space)]);
    $this->actingAs($reader)->get(route('spaces.show', $space))->assertDontSee(route('import.create', $space));
});

it('marks rendered content for the code copy button', function () {
    $user = member();
    $page = page(space([[$user, SpaceRole::Reader]]), ['content' => "```php\necho 'hi';\n```"]);

    $this->actingAs($user)->get($page->url())
        ->assertSee('data-code-copy', false)
        ->assertSee('data-copy-label="'.__('Copy').'"', false)
        ->assertSee('<pre><code class="language-php">', false);
});
