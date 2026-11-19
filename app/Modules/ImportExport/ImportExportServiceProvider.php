<?php

namespace App\Modules\ImportExport;

use App\Core\ImportExport\ExportRegistry;
use App\Core\ImportExport\ImportRegistry;
use App\Modules\ImportExport\Exporters\MarkdownPageExporter;
use App\Modules\ImportExport\Exporters\PageTreeZipExporter;
use App\Modules\ImportExport\Exporters\SpaceZipExporter;
use App\Modules\ImportExport\Importers\MarkdownFileImporter;
use App\Modules\ImportExport\Importers\ZipImporter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ImportExportServiceProvider extends ServiceProvider
{
    public function boot(ImportRegistry $importers, ExportRegistry $exporters): void
    {
        Route::middleware(['web', 'auth', 'account'])->group(__DIR__.'/routes.php');

        $importers->add($this->app->make(MarkdownFileImporter::class));
        $importers->add($this->app->make(ZipImporter::class));

        $exporters->add($this->app->make(MarkdownPageExporter::class));
        $exporters->add($this->app->make(PageTreeZipExporter::class));
        $exporters->add($this->app->make(SpaceZipExporter::class));
    }
}
