<?php

namespace App\Modules\ImportExport\Http\Controllers;

use App\Core\ImportExport\ImportRegistry;
use App\Modules\ImportExport\Services\ImportService;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\PageTree;
use App\Modules\Spaces\Models\Space;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ImportController
{
    public function create(Request $request, Space $space, PageTree $tree, ImportRegistry $importers): View
    {
        Gate::authorize('contribute', $space);

        return view('import.create', [
            'space' => $space,
            'tree' => $tree->forSpace($space),
            'targets' => $tree->flat($space),
            'importers' => $importers->all(),
            'extensions' => $importers->extensions(),
            'selectedParent' => $request->integer('parent') ?: null,
        ]);
    }

    public function store(Request $request, Space $space, ImportService $import, ImportRegistry $importers): RedirectResponse
    {
        Gate::authorize('contribute', $space);

        $config = config('knowledgebase.import');
        $data = $request->validate([
            'mode' => ['required', 'in:file,paste'],
            'parent_id' => ['nullable', 'integer'],
            'file' => ['required_if:mode,file', 'nullable', 'file', 'max:'.$config['max_kb'], 'extensions:'.implode(',', $importers->extensions())],
            'title' => ['nullable', 'string', 'max:255'],
            'markdown' => ['required_if:mode,paste', 'nullable', 'string', 'max:'.$config['max_paste_chars']],
        ]);

        $parent = isset($data['parent_id'])
            ? Page::query()->where('space_id', $space->id)->findOrFail($data['parent_id'])
            : null;

        $result = $data['mode'] === 'file'
            ? $import->importFile($space, $parent, $request->file('file'), $request->user())
            : $import->importPaste($space, $parent, $data['title'] ?? null, (string) $data['markdown'], $request->user());

        $redirect = $result->firstPage !== null && $result->pages === 1
            ? redirect()->route('pages.show', $result->firstPage)
            : redirect()->route('spaces.show', $space);

        return $redirect
            ->with('status', trans_choice('{1} One page imported.|[2,*] :count pages imported.', $result->pages, ['count' => $result->pages]))
            ->with('warnings', $result->warnings);
    }
}
