<?php

namespace App\Core\ImportExport;

use Illuminate\Http\UploadedFile;

interface Importer
{
    public function key(): string;

    /** Untranslated label shown in the import form. */
    public function label(): string;

    /** File extensions this importer handles, lower case without dot. */
    public function extensions(): array;

    /**
     * Turns an uploaded file into documents. Must not touch the database;
     * the import service creates the pages.
     */
    public function read(UploadedFile $file): ImportBundle;
}
