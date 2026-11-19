<?php

namespace App\Modules\Pages\Services;

use App\Core\Activity\ActivityLogger;
use App\Models\User;
use App\Modules\Pages\Contracts\PageWriter;
use App\Modules\Pages\Models\Attachment;
use App\Modules\Pages\Models\Label;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Models\PageVersion;
use App\Modules\Spaces\Models\Space;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * All state changes of pages go through here.
 */
final class PageService implements PageWriter
{
    public function __construct(
        private ActivityLogger $activity,
        private AttachmentService $attachments,
    ) {}

    public function create(Space $space, ?Page $parent, string $title, string $content, User $user, array $labels = []): Page
    {
        if ($parent !== null && $parent->space_id !== $space->id) {
            throw ValidationException::withMessages(['parent_id' => __('The parent page belongs to another space.')]);
        }

        return DB::transaction(function () use ($space, $parent, $title, $content, $user, $labels) {
            $page = new Page(['title' => $this->cleanTitle($title), 'content' => $this->cleanContent($content)]);
            $page->space_id = $space->id;
            $page->parent_id = $parent?->id;
            $page->position = $this->nextPosition($space->id, $parent?->id);
            $page->version = 1;
            $page->created_by = $user->id;
            $page->updated_by = $user->id;
            $page->save();

            $this->snapshot($page, $user);
            $this->syncLabels($page, $labels);
            $this->activity->log('page.created', $page, ['title' => $page->title, 'space_id' => $space->id], $user->id);

            return $page;
        });
    }

    public function update(Page $page, string $title, string $content, User $user, ?array $labels = null): Page
    {
        return DB::transaction(function () use ($page, $title, $content, $user, $labels) {
            $page->fill(['title' => $this->cleanTitle($title), 'content' => $this->cleanContent($content)]);

            if ($page->isDirty(['title', 'content'])) {
                $page->version++;
                $page->updated_by = $user->id;
                $page->save();
                $this->snapshot($page, $user);
                $this->activity->log('page.updated', $page, ['title' => $page->title, 'version' => $page->version], $user->id);
            }

            if ($labels !== null) {
                $this->syncLabels($page, $labels);
            }

            return $page;
        });
    }

    public function restoreVersion(Page $page, PageVersion $version, User $user): Page
    {
        if ($version->page_id !== $page->id) {
            throw ValidationException::withMessages(['version' => __('This version belongs to another page.')]);
        }

        $page = $this->update($page, $version->title, $version->content, $user);
        $this->activity->log('page.version_restored', $page, ['restored' => $version->version], $user->id);

        return $page;
    }

    /**
     * Moves a page under a new parent (or to the top level) in the same space.
     */
    public function move(Page $page, ?Page $newParent, User $user, ?int $position = null): Page
    {
        if ($newParent !== null) {
            if ($newParent->space_id !== $page->space_id) {
                throw ValidationException::withMessages(['parent_id' => __('The parent page belongs to another space.')]);
            }

            if ($newParent->id === $page->id || in_array($newParent->id, $this->descendantIds($page), true)) {
                throw ValidationException::withMessages(['parent_id' => __('A page cannot be moved below itself.')]);
            }
        }

        return DB::transaction(function () use ($page, $newParent, $user, $position) {
            $page->parent_id = $newParent?->id;
            $page->position = $position ?? $this->nextPosition($page->space_id, $newParent?->id);
            $page->save();

            $this->activity->log('page.moved', $page, ['parent_id' => $page->parent_id], $user->id);

            return $page;
        });
    }

    /** Moves the page with all sub-pages to the trash. */
    public function trash(Page $page, User $user): void
    {
        DB::transaction(function () use ($page, $user) {
            $ids = [$page->id, ...$this->descendantIds($page)];
            Page::query()->whereIn('id', $ids)->update(['deleted_at' => now()]);
            $this->activity->log('page.trashed', $page, ['title' => $page->title, 'count' => count($ids)], $user->id);
        });
    }

    /** Restores the page and the sub-pages that were trashed with it. */
    public function restore(Page $page, User $user): Page
    {
        return DB::transaction(function () use ($page, $user) {
            $deletedAt = $page->deleted_at;
            $ids = $this->descendantIds($page, withTrashed: true);

            if ($page->parent_id !== null && ! Page::query()->whereKey($page->parent_id)->exists()) {
                $page->parent_id = null; // parent is gone or still in the trash
            }
            $page->deleted_at = null;
            $page->save();

            Page::onlyTrashed()->whereIn('id', $ids)->where('deleted_at', $deletedAt)->update(['deleted_at' => null]);
            $this->activity->log('page.restored', $page, ['title' => $page->title], $user->id);

            return $page;
        });
    }

    /** Permanently deletes a trashed page, its sub-pages and their files. */
    public function destroy(Page $page, User $user): void
    {
        DB::transaction(function () use ($page, $user) {
            $ids = [$page->id, ...$this->descendantIds($page, withTrashed: true)];

            Attachment::query()->whereIn('page_id', $ids)->get()
                ->each(fn (Attachment $a) => $this->attachments->deleteFile($a));

            // Children first so the self-reference never blocks.
            foreach (array_reverse($ids) as $id) {
                Page::withTrashed()->whereKey($id)->forceDelete();
            }

            $this->activity->log('page.destroyed', null, ['title' => $page->title, 'page_id' => $page->id, 'count' => count($ids)], $user->id);
        });
    }

    public function amendLatestVersion(Page $page, string $content, User $user): Page
    {
        return DB::transaction(function () use ($page, $content) {
            $page->content = $this->cleanContent($content);
            $page->save();
            $page->versions()->where('version', $page->version)->update(['content' => $page->content]);

            return $page;
        });
    }

    public function attachFile(Page $page, string $filename, string $contents, User $user): Attachment
    {
        return $this->attachments->storeContents($page, $filename, $contents, $user);
    }

    /**
     * @return list<int>
     */
    public function descendantIds(Page $page, bool $withTrashed = false): array
    {
        $query = $withTrashed ? Page::withTrashed() : Page::query();
        $all = $query->where('space_id', $page->space_id)->get(['id', 'parent_id']);

        $byParent = [];
        foreach ($all as $row) {
            $byParent[$row->parent_id ?? 0][] = $row->id;
        }

        $result = [];
        $stack = $byParent[$page->id] ?? [];
        while ($stack !== []) {
            $id = array_pop($stack);
            if (in_array($id, $result, true)) {
                continue;
            }
            $result[] = $id;
            array_push($stack, ...($byParent[$id] ?? []));
        }

        return $result;
    }

    /**
     * @param  list<string>  $labels
     */
    private function syncLabels(Page $page, array $labels): void
    {
        $names = LabelNormalizer::normalize($labels);
        $ids = collect($names)->map(fn (string $name) => Label::firstOrCreate(['name' => $name])->id)->all();
        $page->labels()->sync($ids);
    }

    private function snapshot(Page $page, User $user): void
    {
        $page->versions()->create([
            'version' => $page->version,
            'title' => $page->title,
            'content' => $page->content,
            'user_id' => $user->id,
        ]);
    }

    private function nextPosition(int $spaceId, ?int $parentId): int
    {
        return (int) Page::query()->where('space_id', $spaceId)->where('parent_id', $parentId)->max('position') + 1;
    }

    private function cleanTitle(string $title): string
    {
        $title = trim(preg_replace('/\s+/u', ' ', $title) ?? '');

        return mb_substr($title !== '' ? $title : __('Untitled'), 0, 255);
    }

    private function cleanContent(string $content): string
    {
        // Normalise line endings; NUL bytes are not valid in PostgreSQL text.
        return str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $content);
    }
}
