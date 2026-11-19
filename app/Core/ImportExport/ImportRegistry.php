<?php

namespace App\Core\ImportExport;

final class ImportRegistry
{
    /** @var array<string, Importer> */
    private array $importers = [];

    public function add(Importer $importer): void
    {
        $this->importers[$importer->key()] = $importer;
    }

    public function forExtension(string $extension): ?Importer
    {
        $extension = strtolower($extension);

        foreach ($this->importers as $importer) {
            if (in_array($extension, $importer->extensions(), true)) {
                return $importer;
            }
        }

        return null;
    }

    /** @return list<string> */
    public function extensions(): array
    {
        return array_values(array_unique(array_merge(
            ...array_map(fn (Importer $i) => $i->extensions(), array_values($this->importers)),
        )));
    }

    /** @return array<string, Importer> */
    public function all(): array
    {
        return $this->importers;
    }
}
