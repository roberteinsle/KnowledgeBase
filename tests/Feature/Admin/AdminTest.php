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
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

it('keeps members out of the admin area', function () {
    $this->actingAs(member())->get('/admin')->assertForbidden();
    $this->actingAs(member())->get('/admin/benutzer')->assertForbidden();
});

it('shows every admin page to administrators', function (string $url) {
    $this->actingAs(admin())->get($url)->assertOk();
})->with(['/admin', '/admin/benutzer', '/admin/benutzer/neu', '/admin/gruppen', '/admin/gruppen/neu', '/admin/bereiche', '/admin/bereiche/neu', '/admin/labels', '/admin/protokoll', '/admin/einstellungen']);

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
