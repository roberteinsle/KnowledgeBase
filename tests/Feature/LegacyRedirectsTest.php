<?php

use App\Modules\Spaces\Enums\SpaceRole;

it('redirects former German paths to the English ones', function (string $old, string $new) {
    $this->get($old)->assertStatus(301)->assertRedirect($new);
})->with([
    ['/anmelden', '/login'],
    ['/anmelden/zwei-faktor', '/login/two-factor'],
    ['/anmelden/link/'.str_repeat('a', 64), '/login/link/'.str_repeat('a', 64)],
    ['/konto/sicherheit', '/account/security'],
    ['/bestaetigen', '/confirm'],
]);

it('redirects former page, space and attachment paths for signed-in users', function () {
    $user = member();
    $space = space([[$user, SpaceRole::Reader]], ['name' => 'Neu']);
    $page = page($space);

    $this->actingAs($user);
    $this->get("/seiten/{$page->id}/bearbeiten")->assertRedirect("/pages/{$page->id}/edit");
    $this->get("/seiten/{$page->id}/versionen/1")->assertRedirect("/pages/{$page->id}/versions/1");
    $this->get('/anhaenge/5')->assertRedirect('/attachments/5');
    $this->get('/suche?q=urlaub')->assertRedirect('/search?q=urlaub');
    // The space slug is user data and is not translated ("neu" stays "neu").
    $this->get("/bereiche/{$space->slug}/seiten/neu")->assertRedirect("/spaces/{$space->slug}/pages/new");
    $this->get('/admin/benutzer/neu')->assertRedirect('/admin/users/create');
});

it('keeps stored links with former paths working in exports', function () {
    $editor = member();
    $space = space([[$editor, SpaceRole::Editor]]);
    $root = page($space, ['title' => 'Wurzel']);
    page($space, ['title' => 'Kind', 'content' => "[alt](/seiten/{$root->id})"], $root);

    $response = $this->actingAs($editor)->get(route('export.space', [$space, 'space-zip']));
    $zip = new ZipArchive;
    $zip->open($response->baseResponse->getFile()->getPathname());

    expect($zip->getFromName('Wurzel/Kind.md'))->toContain('(<../Wurzel.md>)');
});
