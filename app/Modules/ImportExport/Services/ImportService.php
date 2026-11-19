<?php

namespace App\Modules\ImportExport\Services;

use App\Core\Activity\Activity;
use App\Core\Activity\ActivityLogger;
use App\Core\ImportExport\ImportBundle;
use App\Core\ImportExport\ImportedDocument;
use App\Core\ImportExport\ImportRegistry;
use App\Models\User;
use App\Modules\Pages\Contracts\PageWriter;
use App\Modules\Pages\Models\Page;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Models\Space;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ImportService
{
    public function __construct(
        private ImportRegistry $importers,
        private MarkdownDocumentParser $parser,
        private PageWriter $pages,
        private SpaceAccess $access,
        private ActivityLogger $activity,
    ) {}

    public function importFile(Space $space, ?Page $parent, UploadedFile $file, User $user): ImportResult
    {
        $this->guard($space, $parent, $user);

        $importer = $this->importers->forExtension((string) pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if ($importer === null) {
            throw ValidationException::withMessages(['file' => __('This file type cannot be imported.')]);
        }

        return $this->run($space, $parent, $importer->read($file), $user, $importer->key());
    }

    public function importPaste(Space $space, ?Page $parent, ?string $title, string $markdown, User $user): ImportResult
    {
        $this->guard($space, $parent, $user);

        $document = $this->parser->parse($markdown);
        if ($title !== null && trim($title) !== '') {
            $document->title = mb_substr(trim($title), 0, 255);
        }

        return $this->run($space, $parent, new ImportBundle([$document]), $user, 'paste');
    }

    private function guard(Space $space, ?Page $parent, User $user): void
    {
        // Checked here too, not only in the controller: every entry point is covered.
        if (! $this->access->allows($user, $space, SpaceRole::Editor)) {
            throw new AuthorizationException;
        }

        if ($parent !== null && $parent->space_id !== $space->id) {
            throw ValidationException::withMessages(['parent_id' => __('The parent page belongs to another space.')]);
        }

        $today = Activity::query()
            ->where('user_id', $user->id)
            ->where('action', 'import.completed')
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($today >= (int) config('knowledgebase.import.max_per_user_per_day')) {
            throw ValidationException::withMessages(['file' => __('You have reached the daily import limit. Please try again tomorrow.')]);
        }
    }

    private function run(Space $space, ?Page $parent, ImportBundle $bundle, User $user, string $source): ImportResult
    {
        $result = new ImportResult(warnings: $bundle->warnings);

        DB::transaction(function () use ($space, $parent, $bundle, $user, $result) {
            /** @var list<array{0: ImportedDocument, 1: Page}> $created */
            $created = [];
            foreach ($bundle->documents as $document) {
                $this->create($space, $parent, $document, $user, $created, $result);
            }

            $bySource = [];
            $byTitle = [];
            foreach ($created as [$document, $page]) {
                if ($document->sourcePath !== null) {
                    $bySource[$this->normalize($document->sourcePath)] = $page;
                }
                $byTitle[mb_strtolower($page->title)] ??= $page;
            }

            foreach ($created as [$document, $page]) {
                $rewritten = (new LinkRewriter($bundle->assets, $bySource, $byTitle))
                    ->rewrite($page->content, $document->sourcePath, function (string $path, string $contents) use ($page, $user, $result) {
                        $result->attachments++;

                        return $this->pages->attachFile($page, basename($path), $contents, $user);
                    });

                if ($rewritten !== $page->content) {
                    $this->pages->amendLatestVersion($page, $rewritten, $user);
                }
            }
        });

        $this->activity->log('import.completed', $space, [
            'source' => $source,
            'pages' => $result->pages,
            'attachments' => $result->attachments,
        ], $user->id);

        return $result;
    }

    /**
     * @param  list<array{0: ImportedDocument, 1: Page}>  $created
     */
    private function create(Space $space, ?Page $parent, ImportedDocument $document, User $user, array &$created, ImportResult $result): void
    {
        $page = $this->pages->create($space, $parent, $document->title, $document->markdown, $user, $document->labels);
        $result->pages++;
        $result->firstPage ??= $page;
        $created[] = [$document, $page];

        foreach ($document->children as $child) {
            $this->create($space, $page, $child, $user, $created, $result);
        }
    }

    private function normalize(string $path): string
    {
        return LinkRewriter::normalizePath($path);
    }
}
