<?php

namespace App\Core\ImportExport;

final class ExportRegistry
{
    /** @var array<string, Exporter> */
    private array $exporters = [];

    public function add(Exporter $exporter): void
    {
        $this->exporters[$exporter->key()] = $exporter;
    }

    public function get(string $key): ?Exporter
    {
        return $this->exporters[$key] ?? null;
    }

    /** @return list<Exporter> */
    public function forScope(string $scope): array
    {
        return array_values(array_filter($this->exporters, fn (Exporter $e) => $e->scope() === $scope));
    }
}
