<?php

namespace App\Modules\ImportExport\Http\Controllers;

use App\Core\Activity\ActivityLogger;
use App\Core\ImportExport\ExportRegistry;
use App\Modules\Pages\Models\Page;
use App\Modules\Spaces\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ExportController
{
    public function page(Request $request, Page $page, string $format, ExportRegistry $exports, ActivityLogger $activity): Response
    {
        Gate::authorize('view', $page);

        $exporter = $exports->get($format);
        abort_if($exporter === null || $exporter->scope() !== 'page', 404);

        $activity->log('export.page', $page, ['format' => $format]);

        return $exporter->export($request->user(), $page);
    }

    public function space(Request $request, Space $space, string $format, ExportRegistry $exports, ActivityLogger $activity): Response
    {
        Gate::authorize('view', $space);

        $exporter = $exports->get($format);
        abort_if($exporter === null || $exporter->scope() !== 'space', 404);

        $activity->log('export.space', $space, ['format' => $format]);

        return $exporter->export($request->user(), $space);
    }
}
