<?php

namespace App\Core\Navigation;

use App\Models\User;
use Closure;

/**
 * An entry in a context menu (space links in the sidebar, the "…" menu of a
 * space, the "⋮" menu of a page). Visibility is decided per context object
 * (e.g. the space), not per page, so a menu can be rendered once as a template
 * for all pages of a space.
 */
final readonly class MenuItem
{
    public const LINK = 'link';

    public const POST = 'post';

    public const DELETE = 'delete';

    public const COPY = 'copy';

    /**
     * @param  string  $label  untranslated key
     * @param  'link'|'post'|'delete'|'copy'  $kind  copy = copy the URL to the clipboard
     * @param  Closure(int|string): string  $url  receives the subject id (or a placeholder)
     * @param  (Closure(User, object): bool)|null  $visible  receives the user and the context object
     * @param  string|null  $confirm  untranslated confirmation question
     * @param  string|null  $activeLabel  label when the subject is in the "active" state (e.g. already a favorite)
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $icon,
        public Closure $url,
        public string $kind = self::LINK,
        public int $order = 100,
        public ?Closure $visible = null,
        public ?string $confirm = null,
        public bool $danger = false,
        public ?string $activeLabel = null,
    ) {}

    public function isVisibleFor(User $user, object $context): bool
    {
        return $this->visible === null || ($this->visible)($user, $context);
    }

    public function urlFor(int|string $id): string
    {
        return ($this->url)($id);
    }
}
