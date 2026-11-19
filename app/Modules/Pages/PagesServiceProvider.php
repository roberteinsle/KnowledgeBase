<?php

namespace App\Modules\Pages;

use App\Core\Dashboard\DashboardRegistry;
use App\Core\Mcp\McpToolRegistry;
use App\Core\Navigation\MenuItem;
use App\Core\Navigation\MenuRegistry;
use App\Core\Search\SearchRegistry;
use App\Core\Sidebar\SidebarRegistry;
use App\Models\User;
use App\Modules\Pages\Contracts\PageWriter;
use App\Modules\Pages\Dashboard\RecentPagesWidget;
use App\Modules\Pages\Http\Controllers\PageController;
use App\Modules\Pages\Mcp\CreatePageTool;
use App\Modules\Pages\Mcp\ListPagesTool;
use App\Modules\Pages\Mcp\MovePageTool;
use App\Modules\Pages\Mcp\ReadPageTool;
use App\Modules\Pages\Mcp\TrashPageTool;
use App\Modules\Pages\Mcp\UpdatePageTool;
use App\Modules\Pages\Models\Attachment;
use App\Modules\Pages\Models\Label;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Policies\PagePolicy;
use App\Modules\Pages\Search\AttachmentSearchProvider;
use App\Modules\Pages\Search\PageSearchProvider;
use App\Modules\Pages\Services\AttachmentService;
use App\Modules\Pages\Services\PageService;
use App\Modules\Pages\Services\PageTree;
use App\Modules\Pages\Sidebar\PageTreeSection;
use App\Modules\Pages\Support\CurrentPage;
use App\Modules\Spaces\Contracts\CurrentSpace;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Events\SpaceDeleting;
use App\Modules\Spaces\Models\Space;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PagesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PageWriter::class, PageService::class);
        $this->app->scoped(PageTree::class);
    }

    public function boot(SearchRegistry $search, DashboardRegistry $dashboard, SidebarRegistry $sidebar, MenuRegistry $menus): void
    {
        $sidebar->add($this->app->make(PageTreeSection::class));
        $this->app->make(CurrentSpace::class)->resolveUsing(
            fn (Request $request) => CurrentPage::from($request)?->space,
        );
        $this->registerMenus($menus);

        $tools = $this->app->make(McpToolRegistry::class);
        foreach ([ListPagesTool::class, ReadPageTool::class, CreatePageTool::class, UpdatePageTool::class, MovePageTool::class, TrashPageTool::class] as $tool) {
            $tools->add($tool);
        }

        Relation::morphMap(['page' => Page::class, 'attachment' => Attachment::class, 'label' => Label::class]);
        Gate::policy(Page::class, PagePolicy::class);

        Route::middleware(['web', 'auth', 'account'])->group(__DIR__.'/routes.php');

        // Readable page URLs have no fixed prefix, so they are registered after
        // all other routes (Filament, other modules); reserved space slugs are
        // excluded by the constraint.
        $this->app->booted(function () {
            Route::middleware(['web', 'auth', 'account'])
                ->get('/{spaceSlug}/{pageSlug}', [PageController::class, 'view'])
                ->where('spaceSlug', Space::routePattern())
                ->where('pageSlug', '(?:[a-z0-9-]*-)?\d+')
                ->name('pages.view');
        });

        // Files are not covered by the database cascade.
        Event::listen(SpaceDeleting::class, function (SpaceDeleting $event) {
            $attachments = $this->app->make(AttachmentService::class);
            Attachment::query()
                ->whereIn('page_id', Page::withTrashed()->where('space_id', $event->space->id)->select('id'))
                ->each(fn (Attachment $a) => $attachments->deleteFile($a));
        });

        $search->add($this->app->make(PageSearchProvider::class));
        $search->add($this->app->make(AttachmentSearchProvider::class));
        $dashboard->add($this->app->make(RecentPagesWidget::class));
    }

    private function registerMenus(MenuRegistry $menus): void
    {
        $can = fn (SpaceRole $role) => fn (User $user, Space $space) => app(SpaceAccess::class)->allows($user, $space, $role);

        // Links below the space switcher (context: space, id: slug)
        $menus->add('space', new MenuItem('overview', 'Overview', 'home', fn ($slug) => route('spaces.show', $slug), order: 10));
        $menus->add('space', new MenuItem('new-page', 'New page', 'plus', fn ($slug) => route('pages.create', $slug), order: 40, visible: $can(SpaceRole::Editor)));

        // "…" next to "Pages"
        $menus->add('space.pages', new MenuItem('trash', 'Trash', 'trash', fn ($slug) => route('trash.index', $slug), order: 90, visible: $can(SpaceRole::Admin)));

        // "⋮" of a page (id: page id)
        $menus->add('page', new MenuItem('copy-link', 'Copy link', 'link', fn ($id) => route('pages.show', $id), kind: MenuItem::COPY, order: 10));
        $menus->add('page', new MenuItem('favorite', 'Add to favorites', 'star', fn ($id) => route('pages.favorite', $id), kind: MenuItem::POST, order: 20, activeLabel: 'Remove from favorites'));
        $menus->add('page', new MenuItem('duplicate', 'Duplicate', 'duplicate', fn ($id) => route('pages.duplicate', $id), kind: MenuItem::POST, order: 40, visible: $can(SpaceRole::Editor)));
        $menus->add('page', new MenuItem('move', 'Move', 'move', fn ($id) => route('pages.move', $id), order: 50, visible: $can(SpaceRole::Editor)));
        $menus->add('page', new MenuItem('copy', 'Copy to space', 'duplicate', fn ($id) => route('pages.copy', $id), order: 60));
        $menus->add('page', new MenuItem('version-history', 'Version history', 'clock', fn ($id) => route('pages.versions', $id), order: 70));
        $menus->add('page', new MenuItem('trash', 'Move to trash', 'trash', fn ($id) => route('pages.destroy', $id), kind: MenuItem::DELETE, order: 90,
            visible: $can(SpaceRole::Admin), confirm: 'Move this page and all its sub-pages to the trash?', danger: true));
    }
}
