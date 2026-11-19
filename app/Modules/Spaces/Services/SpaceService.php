<?php

namespace App\Modules\Spaces\Services;

use App\Core\Activity\ActivityLogger;
use App\Models\User;
use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Events\SpaceDeleting;
use App\Modules\Spaces\Models\Space;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SpaceService
{
    public function __construct(
        private ActivityLogger $activity,
        private SpaceAccessService $access,
    ) {}

    /**
     * @param  array{name: string, slug?: string|null, description?: string|null, icon?: string|null}  $data
     * @param  list<array{subject_type: string, subject_id: int, role: string}>  $permissions
     */
    public function create(array $data, User $creator, array $permissions = []): Space
    {
        return DB::transaction(function () use ($data, $creator, $permissions) {
            $space = new Space;
            $space->fill([...$data, 'slug' => $this->uniqueSlug($data['slug'] ?? null ?: $data['name'])]);
            $space->created_by = $creator->id;
            $space->save();

            $this->syncPermissions($space, $permissions);
            $this->activity->log('space.created', $space, ['name' => $space->name]);

            return $space;
        });
    }

    /**
     * @param  array{name: string, slug?: string|null, description?: string|null, icon?: string|null}  $data
     * @param  list<array{subject_type: string, subject_id: int, role: string}>|null  $permissions
     */
    public function update(Space $space, array $data, ?array $permissions = null): Space
    {
        return DB::transaction(function () use ($space, $data, $permissions) {
            if (! isset($data['slug']) || $data['slug'] === '') {
                unset($data['slug']);
            } elseif ($data['slug'] !== $space->slug) {
                $data['slug'] = $this->uniqueSlug($data['slug'], $space->id);
            }
            $space->fill($data)->save();

            if ($permissions !== null) {
                $this->syncPermissions($space, $permissions);
            }

            $this->activity->log('space.updated', $space, $space->getChanges());

            return $space;
        });
    }

    public function delete(Space $space): void
    {
        DB::transaction(function () use ($space) {
            SpaceDeleting::dispatch($space);
            $this->activity->log('space.deleted', $space, ['name' => $space->name]);
            $space->delete();
        });
        $this->access->flush();
    }

    /**
     * @param  list<array{subject_type: string, subject_id: int, role: string}>  $permissions
     */
    public function syncPermissions(Space $space, array $permissions): void
    {
        $rows = collect($permissions)
            ->filter(fn ($p) => in_array($p['subject_type'], ['user', 'group'], true) && SpaceRole::tryFrom($p['role']) !== null)
            ->keyBy(fn ($p) => $p['subject_type'].':'.$p['subject_id']);

        $space->permissions()->delete();
        foreach ($rows as $row) {
            $space->permissions()->create([
                'subject_type' => $row['subject_type'],
                'subject_id' => (int) $row['subject_id'],
                'role' => $row['role'],
            ]);
        }

        $this->access->flush();
        $this->activity->log('space.permissions_updated', $space, ['count' => $rows->count()]);
    }

    public function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'space';
        $base = Str::limit($base, 90, '');
        $slug = $base;
        $i = 2;

        while (Space::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
