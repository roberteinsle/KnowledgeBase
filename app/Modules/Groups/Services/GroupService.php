<?php

namespace App\Modules\Groups\Services;

use App\Core\Activity\ActivityLogger;
use App\Models\User;
use App\Modules\Groups\Contracts\GroupMembership;
use App\Modules\Groups\Models\Group;
use Illuminate\Validation\ValidationException;

final class GroupService implements GroupMembership
{
    /** @var array<int, list<int>> */
    private array $cache = [];

    public function __construct(private ActivityLogger $activity) {}

    public function groupIdsFor(User $user): array
    {
        return $this->cache[$user->id] ??= array_values(array_unique(array_map('intval', [
            Group::everyone()->id,
            ...$user->groups()->pluck('groups.id')->all(),
        ])));
    }

    /**
     * @param  array{name: string, description?: string|null}  $data
     * @param  list<int>  $userIds
     */
    public function create(array $data, array $userIds = []): Group
    {
        $group = Group::create($data);
        $group->users()->sync($userIds);
        $this->cache = [];

        $this->activity->log('group.created', $group, ['name' => $group->name]);

        return $group;
    }

    /**
     * @param  array{name: string, description?: string|null}  $data
     * @param  list<int>|null  $userIds
     */
    public function update(Group $group, array $data, ?array $userIds = null): Group
    {
        $group->fill($data)->save();

        if ($userIds !== null && ! $group->isEveryone()) {
            $group->users()->sync($userIds);
        }
        $this->cache = [];

        $this->activity->log('group.updated', $group, $group->getChanges());

        return $group;
    }

    public function delete(Group $group): void
    {
        if ($group->isEveryone()) {
            throw ValidationException::withMessages(['group' => __('The Everyone group cannot be deleted.')]);
        }

        $this->activity->log('group.deleted', $group, ['name' => $group->name]);
        $group->delete();
        $this->cache = [];
    }
}
