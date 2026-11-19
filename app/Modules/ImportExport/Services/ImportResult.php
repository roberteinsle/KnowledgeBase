<?php

namespace App\Modules\ImportExport\Services;

use App\Modules\Pages\Models\Page;

final class ImportResult
{
    /**
     * @param  list<string>  $warnings
     */
    public function __construct(
        public int $pages = 0,
        public int $attachments = 0,
        public ?Page $firstPage = null,
        public array $warnings = [],
    ) {}
}
