<?php

use App\Models\User;
use App\Modules\Auth\Models\MagicLink;
use App\Modules\Groups\Models\Group;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\PageService;
use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Models\Space;
use App\Modules\Spaces\Services\SpaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/*
| Helpers. Test data only with invented people and example.com.
*/

function admin(array $attributes = []): User
{
    return User::factory()->admin()->create(['name' => 'Erika Mustermann', 'email' => 'erika@example.com', ...$attributes]);
}

function member(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

/**
 * @param  array<int, array{0: User|Group, 1: SpaceRole}>  $grants
 */
function space(array $grants = [], array $attributes = []): Space
{
    $creator = User::query()->where('role', User::ROLE_ADMIN)->first() ?? User::factory()->admin()->create();

    return app(SpaceService::class)->create(
        ['name' => $attributes['name'] ?? 'Handbuch '.uniqid(), ...$attributes],
        $creator,
        array_map(fn (array $grant) => [
            'subject_type' => $grant[0] instanceof Group ? 'group' : 'user',
            'subject_id' => $grant[0]->id,
            'role' => $grant[1]->value,
        ], $grants),
    );
}

function page(Space $space, array $attributes = [], ?Page $parent = null, ?User $user = null): Page
{
    $user ??= User::query()->where('role', User::ROLE_ADMIN)->first() ?? User::factory()->admin()->create();

    return app(PageService::class)->create(
        $space,
        $parent,
        $attributes['title'] ?? 'Seite '.uniqid(),
        $attributes['content'] ?? '',
        $user,
        $attributes['labels'] ?? [],
    );
}

/** Creates a valid magic link and returns the plain token. */
function magicToken(User $user, array $attributes = []): string
{
    $token = bin2hex(random_bytes(32));
    $link = new MagicLink;
    $link->user_id = $user->id;
    $link->token_hash = hash('sha256', $token);
    $link->expires_at = $attributes['expires_at'] ?? now()->addMinutes(15);
    $link->used_at = $attributes['used_at'] ?? null;
    $link->redirect_to = $attributes['redirect_to'] ?? null;
    $link->save();

    return $token;
}

/** The SQL injection payloads required by the standards, plain and URL-encoded. */
function sqlInjectionPayloads(): array
{
    $payloads = [
        "' OR '1'='1",
        "' OR 1=1 --",
        "admin'--",
        "'; DROP TABLE users; --",
        '" OR ""="',
        "1' UNION SELECT NULL--",
    ];

    return [...$payloads, ...array_map('rawurlencode', $payloads)];
}
