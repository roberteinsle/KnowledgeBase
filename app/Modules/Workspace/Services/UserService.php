<?php

namespace App\Modules\Workspace\Services;

use App\Core\Activity\ActivityLogger;
use App\Models\User;
use App\Modules\Groups\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Workspace membership. Guarantees there is always at least one active admin.
 */
final class UserService
{
    public function __construct(private ActivityLogger $activity) {}

    /**
     * @param  array{name: string, email: string, locale?: string|null, role: string, is_active: bool}  $data
     * @param  list<int>  $groupIds
     */
    public function create(array $data, array $groupIds = []): User
    {
        return DB::transaction(function () use ($data, $groupIds) {
            $user = new User(['name' => $data['name'], 'email' => $data['email'], 'locale' => $data['locale'] ?? null]);
            $user->forceFill([
                'role' => $this->role($data['role']),
                'is_active' => (bool) $data['is_active'],
            ])->save();

            $user->groups()->sync($this->assignableGroups($groupIds));
            $this->activity->log('user.created', $user, ['email' => $user->email, 'role' => $user->role]);

            return $user;
        });
    }

    /**
     * @param  array{name: string, email: string, locale?: string|null, role: string, is_active: bool}  $data
     * @param  list<int>|null  $groupIds
     */
    public function update(User $user, array $data, ?array $groupIds = null): User
    {
        return DB::transaction(function () use ($user, $data, $groupIds) {
            $user->fill(['name' => $data['name'], 'email' => $data['email'], 'locale' => $data['locale'] ?? null]);
            $user->forceFill(['role' => $this->role($data['role']), 'is_active' => (bool) $data['is_active']]);

            if ($user->isDirty(['role', 'is_active']) && (! $user->isAdmin() || ! $user->is_active)) {
                $this->ensureAnotherAdmin($user);
            }

            $user->save();

            if ($groupIds !== null) {
                $user->groups()->sync($this->assignableGroups($groupIds));
            }

            $this->activity->log('user.updated', $user, $user->getChanges());

            return $user;
        });
    }

    public function delete(User $user): void
    {
        if ($user->isAdmin()) {
            $this->ensureAnotherAdmin($user);
        }

        $this->activity->log('user.deleted', $user, ['email' => $user->email]);
        $user->delete();
    }

    private function ensureAnotherAdmin(User $user): void
    {
        $others = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->exists();

        if (! $others) {
            throw ValidationException::withMessages(['role' => __('At least one active administrator must remain.')]);
        }
    }

    private function role(string $role): string
    {
        return $role === User::ROLE_ADMIN ? User::ROLE_ADMIN : User::ROLE_MEMBER;
    }

    /**
     * @param  list<int>  $groupIds
     * @return list<int>
     */
    private function assignableGroups(array $groupIds): array
    {
        return Group::query()->whereIn('id', $groupIds)->whereNull('system_key')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
