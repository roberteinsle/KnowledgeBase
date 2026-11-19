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

    public const BEFORE = 'before';

    public const AFTER = 'after';

    public const INSIDE = 'inside';

    /**
     * Drag & drop in the page tree: places the page before or after the target
     * (same parent as the target) or inside it (as last sub-page). Siblings are
     * renumbered so the order is gap-free.
     */
    public function place(Page $page, Page $target, string $placement, User $user): Page
    {
        if (! in_array($placement, [self::BEFORE, self::AFTER, self::INSIDE], true)) {
            throw ValidationException::withMessages(['placement' => __('Invalid position.')]);
        }

        if ($target->space_id !== $page->space_id) {
            throw ValidationException::withMessages(['target_id' => __('The parent page belongs to another space.')]);
        }

        $newParentId = $placement === self::INSIDE ? $target->id : $target->parent_id;

        if ($target->id === $page->id || ($newParentId !== null && ($newParentId === $page->id || in_array($newParentId, $this->descendantIds($page), true)))) {
            throw ValidationException::withMessages(['target_id' => __('A page cannot be moved below itself.')]);
        }

        return DB::transaction(function () use ($page, $target, $placement, $newParentId, $user) {
            $siblings = Page::query()
                ->where('space_id', $page->space_id)
                ->where('parent_id', $newParentId)
                ->whereKeyNot($page->id)
                ->orderBy('position')->orderBy('id')
                ->pluck('id')
                ->all();

            $index = match ($placement) {
                self::INSIDE => count($siblings),
                self::BEFORE => (int) array_search($target->id, $siblings, true),
                self::AFTER => (int) array_search($target->id, $siblings, true) + 1,
            };
            array_splice($siblings, $index, 0, [$page->id]);

            $page->parent_id = $newParentId;
            $page->save();

            foreach ($siblings as $position => $id) {
                Page::query()->whereKey($id)->update(['position' => $position + 1]);
            }

            $this->activity->log('page.moved', $page, ['parent_id' => $newParentId, 'placement' => $placement], $user->id);

            return $page->refresh();
        });
    }

    /**
     * Copies a page with its sub-pages, labels and attachments. Without a
     * target space the copy is placed right after the original ("Duplicate"),
     * otherwise at the end of the target parent in the target space.
     */
    public function duplicate(Page $source, User $user, ?Space $targetSpace = null, ?Page $targetParent = null): Page
    {
        $sameSpace = $targetSpace === null;
        $targetSpace ??= $source->space;
        $parent = $sameSpace ? $source->parent : $targetParent;

        if ($parent !== null && $parent->space_id !== $targetSpace->id) {
            throw ValidationException::withMessages(['parent_id' => __('The parent page belongs to another space.')]);
        }

        return DB::transaction(function () use ($source, $user, $targetSpace, $parent, $sameSpace) {
            $title = $sameSpace ? __(':title (copy)', ['title' => $source->title]) : $source->title;
            $copy = $this->copyTree($source, $targetSpace, $parent, $user, $title);

            if ($sameSpace) {
                $this->place($copy, $source, self::AFTER, $user);
            }

            $this->activity->log('page.duplicated', $copy, ['source_id' => $source->id, 'space_id' => $targetSpace->id], $user->id);

            return $copy;
        });
    }

    private function copyTree(Page $source, Space $space, ?Page $parent, User $user, ?string $title = null): Page
    {
        $source->loadMissing(['labels', 'attachments']);
        $copy = $this->create($space, $parent, $title ?? $source->title, $source->content, $user, $source->labels->pluck('name')->all());

        // Copy files and point links in the content to the copies.
        $content = $copy->content;
        foreach ($source->attachments as $attachment) {
            $contents = $this->attachments->read($attachment);
            if ($contents === null) {
                continue;
            }
            $new = $this->attachments->storeContents($copy, $attachment->original_name, $contents, $user);
            $content = preg_replace('#\((/(?:attachments|anhaenge)/'.$attachment->id.')\)#', '('.route('attachments.show', $new, false).')', $content) ?? $content;
        }
        if ($content !== $copy->content) {
            $this->amendLatestVersion($copy, $content, $user);
        }

        foreach ($source->children()->get() as $child) {
            $this->copyTree($child, $space, $copy, $user);
        }

        return $copy;
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
