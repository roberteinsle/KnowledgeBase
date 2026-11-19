<?php

namespace App\Filament\Widgets;

use App\Core\Admin\AdminMetric;
use App\Core\Admin\AdminMetricRegistry;
use App\Filament\Concerns\AdminOnly;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Key figures registered by the modules (AdminMetricRegistry). */
class KeyFiguresWidget extends StatsOverviewWidget
{
    use AdminOnly;

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        return array_map(function (AdminMetric $metric) {
            $stat = Stat::make(__($metric->label), ($metric->value)())->icon($metric->icon);

            if ($metric->description !== null && ($description = ($metric->description)()) !== null) {
                $stat->description($description);
            }
            if ($metric->route !== null) {
                $stat->url(route($metric->route));
            }

            return $stat;
        }, app(AdminMetricRegistry::class)->all());
    }

    public static function canView(): bool
    {
        return static::canAccess();
    }
}
