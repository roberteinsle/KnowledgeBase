<?php

use App\Modules\Spaces\Enums\SpaceRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('local'));

it('generates an initials avatar without a profile picture', function () {
    $user = member(['name' => 'Erika Mustermann']);

    $this->actingAs($user)->get(route('avatars.user', $user->id))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertSee('>EM<', false);
});

it('escapes the name in the generated avatar', function () {
    $user = member(['name' => '<script> x']);

    $response = $this->actingAs($user)->get(route('avatars.user', $user->id));
    expect($response->getContent())->not->toContain('<script>');
});

it('uploads, shows and removes a profile picture', function () {
    $user = member();

    $this->actingAs($user)->post(route('account.avatar.store'), ['avatar' => UploadedFile::fake()->image('me.png', 120, 120)])
        ->assertRedirect()->assertSessionHasNoErrors();

    $path = $user->fresh()->avatar_path;
    expect($path)->toStartWith('avatars/');
    Storage::disk('local')->assertExists($path);
    $this->actingAs($user)->get(route('avatars.user', $user->id))->assertOk()->assertHeader('Content-Type', 'image/png');

    $this->actingAs($user)->delete(route('account.avatar.destroy'))->assertRedirect();
    expect($user->fresh()->avatar_path)->toBeNull();
    Storage::disk('local')->assertMissing($path);
});

it('replaces the old file on a new upload', function () {
    $user = member();
    $this->actingAs($user)->post(route('account.avatar.store'), ['avatar' => UploadedFile::fake()->image('a.png')]);
    $first = $user->fresh()->avatar_path;

    $this->actingAs($user)->post(route('account.avatar.store'), ['avatar' => UploadedFile::fake()->image('b.png')]);

    Storage::disk('local')->assertMissing($first);
    Storage::disk('local')->assertExists($user->fresh()->avatar_path);
});

it('rejects SVG, other files and oversized images', function (UploadedFile $file) {
    $user = member();

    $this->actingAs($user)->post(route('account.avatar.store'), ['avatar' => $file])->assertSessionHasErrors('avatar');
    expect($user->fresh()->avatar_path)->toBeNull();
})->with([
    'svg' => fn () => UploadedFile::fake()->createWithContent('a.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    'pdf' => fn () => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
    'too large' => fn () => UploadedFile::fake()->image('big.png')->size(2048),
]);

it('requires sign-in to see avatars', function () {
    $user = member();

    $this->get(route('avatars.user', $user->id))->assertRedirect('/login');
});

it('shows the avatar in the header, on the account page and as page editor', function () {
    $user = member();
    $space = space([[$user, SpaceRole::Editor]]);
    $page = page($space, [], null, $user);

    $this->actingAs($user)->get('/account')->assertSee(route('avatars.user', $user->id), false)->assertSee(__('Profile picture'));
    $this->actingAs($user)->get($page->url())->assertSee(route('avatars.user', $user->id), false);
});
