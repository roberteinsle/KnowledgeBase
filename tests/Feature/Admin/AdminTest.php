<?php

use App\Filament\Pages\WorkspaceSettings;
use App\Filament\Resources\Groups\Pages\CreateGroup;
use App\Filament\Resources\Spaces\Pages\CreateSpace;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use App\Modules\Groups\Models\Group;
use App\Modules\Groups\Services\GroupService;
use App\Modules\Spaces\Models\Space;
use App\Modules\Workspace\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

it('keeps members out of the admin area', function () {
    $this->actingAs(member())->get('/admin')->assertForbidden();
    $this->actingAs(member())->get('/admin/users')->assertForbidden();
});

it('shows every admin page to administrators', function (string $url) {
    $this->actingAs(admin())->get($url)->assertOk();
})->with(['/admin', '/admin/users', '/admin/users/create', '/admin/groups', '/admin/groups/create', '/admin/spaces', '/admin/spaces/create', '/admin/labels', '/admin/activity-log', '/admin/settings']);

it('creates users through the service with normalised address', function () {
    $this->actingAs(admin());

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Max Mustermann', 'email' => 'Max@Example.COM', 'role' => 'member', 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'max@example.com')->sole();
    expect($user->role)->toBe('member');
});

it('keeps at least one active administrator', function () {
    $admin = admin();
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
        ->fillForm(['role' => 'member'])
        ->call('save');

    expect($admin->fresh()->isAdmin())->toBeTrue();
});

it('creates spaces with permissions', function () {
    $this->actingAs(admin());
    $group = Group::everyone();

    Livewire::test(CreateSpace::class)
        ->fillForm([
            'name' => 'Personal & Recht',
            'permissions' => [['subject_type' => 'group', 'subject_id' => $group->id, 'role' => 'reader']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $space = Space::query()->sole();
    expect($space->slug)->toBe('personal-recht');
    expect($space->permissions()->sole()->role->value)->toBe('reader');
});

it('creates groups with members', function () {
    $this->actingAs(admin());
    $member = member();

    Livewire::test(CreateGroup::class)
        ->fillForm(['name' => 'Redaktion', 'users' => [$member->id]])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Group::query()->where('name', 'Redaktion')->sole()->users->pluck('id')->all())->toBe([$member->id]);
});

it('does not delete the Everyone group', function () {
    expect(fn () => app(GroupService::class)->delete(Group::everyone()))
        ->toThrow(ValidationException::class);
});

it('saves workspace settings', function () {
    $this->actingAs(admin());

    Livewire::test(WorkspaceSettings::class)
        ->fillForm(['name' => 'Wissen GmbH', 'default_locale' => 'fr', 'enforce_mfa' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Workspace::current()->name)->toBe('Wissen GmbH');
    expect(Workspace::current()->default_locale)->toBe('fr');
});

it('uploads a space icon through the admin form', function () {
    Storage::fake('local');
    $this->actingAs(admin());

    Livewire::test(CreateSpace::class)
        ->fillForm(['name' => 'Mit Bild', 'icon_path' => UploadedFile::fake()->image('logo.png', 64, 64)])
        ->call('create')
        ->assertHasNoFormErrors();

    $space = Space::query()->where('name', 'Mit Bild')->sole();
    expect($space->icon_path)->toStartWith('space-icons/');
    Storage::disk('local')->assertExists($space->icon_path);
});

it('rejects SVG files as space icon in the admin form', function () {
    Storage::fake('local');
    $this->actingAs(admin());

    Livewire::test(CreateSpace::class)
        ->fillForm(['name' => 'Mit SVG', 'icon_path' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>')])
        ->call('create')
        ->assertHasFormErrors(['icon_path']);
});
