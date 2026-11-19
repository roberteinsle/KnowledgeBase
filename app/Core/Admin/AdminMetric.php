<?php

namespace App\Core\Admin;

use Closure;

/**
 * A key figure on the admin dashboard. Value and description are computed
 * only when the dashboard is rendered.
 */
final readonly class AdminMetric
{
    /**
     * @param  string  $label  untranslated key
     * @param  Closure(): (int|string)  $value
     * @param  (Closure(): ?string)|null  $description  already translated text
     * @param  string|null  $route  route name of the link target (e.g. the admin list), resolved when rendered
     */
    public function __construct(
        public string $key,
        public string $label,
        public Closure $value,
        public string $icon = 'heroicon-o-chart-bar',
        public int $order = 100,
        public ?Closure $description = null,
        public ?string $route = null,
    ) {}
}
