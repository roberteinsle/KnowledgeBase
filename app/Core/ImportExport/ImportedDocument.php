<?php

namespace App\Core\ImportExport;

final class ImportedDocument
{
    /**
     * @param  list<string>  $labels
     * @param  list<ImportedDocument>  $children
     * @param  array<string, string>  $assets  relative path => binary content (images etc.)
     */
    public function __construct(
        public string $title,
        public string $markdown,
        public array $labels = [],
        public array $children = [],
        public array $assets = [],
        public ?string $sourcePath = null,
    ) {}
}
