<?php

namespace App\Modules\ImportExport\Importers;

use App\Core\ImportExport\ImportBundle;
use App\Core\ImportExport\Importer;
use App\Modules\ImportExport\Services\MarkdownDocumentParser;
use Illuminate\Http\UploadedFile;

class MarkdownFileImporter implements Importer
{
    public function __construct(private MarkdownDocumentParser $parser) {}

    public function key(): string
    {
        return 'markdown';
    }

    public function label(): string
    {
        return 'Markdown file (.md)';
    }

    public function extensions(): array
    {
        return ['md', 'markdown', 'txt'];
    }

    public function read(UploadedFile $file): ImportBundle
    {
        $name = $file->getClientOriginalName();

        return new ImportBundle([
            $this->parser->parse(
                (string) file_get_contents($file->getRealPath()),
                MarkdownDocumentParser::titleFromFilename($name),
                basename($name),
            ),
        ]);
    }
}
