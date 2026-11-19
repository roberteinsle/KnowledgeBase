<?php

namespace App\Modules\ImportExport;

use App\Core\ImportExport\ExportRegistry;
use App\Core\ImportExport\ImportRegistry;
use App\Core\Navigation\MenuItem;
use App\Core\Navigation\MenuRegistry;
use App\Models\User;
use App\Modules\ImportExport\Exporters\MarkdownPageExporter;
use App\Modules\ImportExport\Exporters\PageTreeZipExporter;
use App\Modules\ImportExport\Exporters\SpaceZipExporter;
use App\Modules\ImportExport\Importers\MarkdownFileImporter;
use App\Modules\ImportExport\Importers\ZipImporter;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Models\Space;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ImportExportServiceProvider extends ServiceProvider
{
    public function boot(ImportRegistry $importers, ExportRegistry $exporters, MenuRegistry $menus): void
    {
        Route::middleware(['web', 'auth', 'account'])->group(__DIR__.'/routes.php');

        $importers->add($this->app->make(MarkdownFileImporter::class));
        $importers->add($this->app->make(ZipImporter::class));

        $exporters->add($this->app->make(MarkdownPageExporter::class));
        $exporters->add($this->app->make(PageTreeZipExporter::class));
        $exporters->add($this->app->make(SpaceZipExporter::class));

        $menus->add('space.pages', new MenuItem('import', 'Import Markdown', 'upload', fn ($slug) => route('import.create', $slug), order: 10,
            visible: fn (User $user, Space $space) => app(SpaceAccess::class)->allows($user, $space, SpaceRole::Editor)));

        $order = 20;
        foreach ($exporters->forScope('space') as $exporter) {
            $menus->add('space.pages', new MenuItem('export-'.$exporter->key(), $exporter->label(), 'download', fn ($slug) => route('export.space', [$slug, $exporter->key()]), order: $order++));
        }

        $order = 30;
        foreach ($exporters->forScope('page') as $exporter) {
            $menus->add('page', new MenuItem('export-'.$exporter->key(), $exporter->label(), 'download', fn ($id) => route('export.page', [$id, $exporter->key()]), order: $order++));
        }
    }
}
