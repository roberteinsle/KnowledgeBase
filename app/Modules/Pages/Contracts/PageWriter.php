<?php

namespace App\Modules\Pages\Contracts;

use App\Models\User;
use App\Modules\Pages\Models\Attachment;
use App\Modules\Pages\Models\Page;
use App\Modules\Spaces\Models\Space;

/**
 * What other modules (import, later MCP and Bases) may use to change pages.
 */
interface PageWriter
{
    /**
     * @param  list<string>  $labels
     */
    public function create(Space $space, ?Page $parent, string $title, string $content, User $user, array $labels = []): Page;

    /**
     * @param  list<string>|null  $labels  null keeps the current labels
     */
    public function update(Page $page, string $title, string $content, User $user, ?array $labels = null): Page;

    /**
     * Corrects the content of the current version without creating a new one.
     * Only for machine fix-ups right after creation (import link rewriting).
     */
    public function amendLatestVersion(Page $page, string $content, User $user): Page;

    public function attachFile(Page $page, string $filename, string $contents, User $user): Attachment;
}
