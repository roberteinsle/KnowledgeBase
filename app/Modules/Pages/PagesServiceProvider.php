<?php

namespace App\Modules\Pages;

use App\Core\Dashboard\DashboardRegistry;
use App\Core\Search\SearchRegistry;
use App\Modules\Pages\Contracts\PageWriter;
use App\Modules\Pages\Dashboard\RecentPagesWidget;
use App\Modules\Pages\Models\Attachment;
use App\Modules\Pages\Models\Label;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Policies\PagePolicy;
use App\Modules\Pages\Search\AttachmentSearchProvider;
use App\Modules\Pages\Search\PageSearchProvider;
use App\Modules\Pages\Services\AttachmentService;
use App\Modules\Pages\Services\PageService;
use App\Modules\Pages\Services\PageTree;
use App\Modules\Spaces\Events\SpaceDeleting;
use Illuminate\Database\Eloquent\Relations\Relation;
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

    public function boot(SearchRegistry $search, DashboardRegistry $dashboard): void
    {
        Relation::morphMap(['page' => Page::class, 'attachment' => Attachment::class, 'label' => Label::class]);
        Gate::policy(Page::class, PagePolicy::class);

        Route::middleware(['web', 'auth', 'account'])->group(__DIR__.'/routes.php');

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
}
