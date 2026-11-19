<?php

namespace App\Core\ImportExport;

final class ImportBundle
{
    /**
     * @param  list<ImportedDocument>  $documents  top-level documents (with nested children)
     * @param  array<string, string>  $assets  path inside the archive => binary content
     * @param  list<string>  $warnings  untranslated messages with :placeholders
     */
    public function __construct(
        public array $documents = [],
        public array $assets = [],
        public array $warnings = [],
    ) {}
}
