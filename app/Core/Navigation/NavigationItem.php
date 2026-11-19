<?php

namespace App\Core\Navigation;

use App\Models\User;
use Closure;

final readonly class NavigationItem
{
    /**
     * @param  string  $label  untranslated key, passed through __() when rendered
     * @param  string  $icon  name of a Blade icon component in resources/views/components/icons
     * @param  'main'|'account'  $area
     * @param  bool  $mobileTab  shown in the bottom tab bar on small screens (keep to 3–5 items)
     * @param  list<string>  $activeRoutes  route name patterns that mark the item as active
     * @param  (Closure(User): bool)|null  $visible
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $route,
        public string $icon,
        public string $area = 'main',
        public int $order = 100,
        public bool $mobileTab = false,
        public array $activeRoutes = [],
        public ?Closure $visible = null,
    ) {}

    public function isVisibleFor(User $user): bool
    {
        return $this->visible === null || ($this->visible)($user);
    }
}
