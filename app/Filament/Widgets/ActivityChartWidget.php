<?php

namespace App\Filament\Widgets;

use App\Core\Activity\Activity;
use App\Filament\Concerns\AdminOnly;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/** Changes to content and sign-ins over the last 30 days (activity log). */
class ActivityChartWidget extends ChartWidget
{
    use AdminOnly;

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    public function getHeading(): string
    {
        return __('Activity in the last 30 days');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $from = now()->subDays(29)->startOfDay();
        $rows = Activity::query()
            ->where('created_at', '>=', $from)
            ->selectRaw("date_trunc('day', created_at)::date AS day, action LIKE 'page.%' AS is_page, action = 'auth.login' AS is_login, count(*) AS total")
            ->groupBy('day', 'is_page', 'is_login')
            ->toBase()
            ->get();

        $days = collect(range(0, 29))->map(fn (int $i) => $from->copy()->addDays($i)->toDateString());
        $series = fn (string $flag) => $days->map(fn (string $day) => (int) $rows
            ->filter(fn ($row) => Carbon::parse($row->day)->toDateString() === $day && $row->{$flag})
            ->sum('total'))->all();

        return [
            'datasets' => [
                ['label' => __('Page changes'), 'data' => $series('is_page'), 'borderColor' => '#4f46e5', 'backgroundColor' => 'rgba(79,70,229,.1)', 'fill' => true, 'tension' => .3],
                ['label' => __('Sign-ins'), 'data' => $series('is_login'), 'borderColor' => '#0d9488', 'backgroundColor' => 'transparent', 'tension' => .3],
            ],
            'labels' => $days->map(fn (string $day) => Carbon::parse($day)->isoFormat('D.M.'))->all(),
        ];
    }

    public static function canView(): bool
    {
        return static::canAccess();
    }
}
