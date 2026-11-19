<?php

use App\Core\CoreServiceProvider;
use App\Modules\Auth\AuthServiceProvider;
use App\Modules\Groups\GroupsServiceProvider;
use App\Modules\ImportExport\ImportExportServiceProvider;
use App\Modules\Pages\PagesServiceProvider;
use App\Modules\Search\SearchServiceProvider;
use App\Modules\Spaces\SpacesServiceProvider;
use App\Modules\Workspace\WorkspaceServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    WorkspaceServiceProvider::class,
    AuthServiceProvider::class,
    GroupsServiceProvider::class,
    SpacesServiceProvider::class,
    PagesServiceProvider::class,
    SearchServiceProvider::class,
    ImportExportServiceProvider::class,
    AdminPanelProvider::class,
];
