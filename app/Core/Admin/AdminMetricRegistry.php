<?php

namespace App\Core\Admin;

/** Key figures on the admin dashboard; every module registers its own. */
final class AdminMetricRegistry
{
    /** @var array<string, AdminMetric> */
    private array $metrics = [];

    public function add(AdminMetric $metric): void
    {
        $this->metrics[$metric->key] = $metric;
    }

    /** @return list<AdminMetric> */
    public function all(): array
    {
        $metrics = array_values($this->metrics);
        usort($metrics, fn (AdminMetric $a, AdminMetric $b) => $a->order <=> $b->order);

        return $metrics;
    }
}
