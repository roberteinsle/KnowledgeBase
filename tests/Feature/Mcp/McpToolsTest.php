<?php

use App\Core\Activity\Activity;
use App\Modules\Mcp\Servers\KnowledgebaseServer;
use App\Modules\Pages\Mcp\CreatePageTool;
use App\Modules\Pages\Mcp\ListPagesTool;
use App\Modules\Pages\Mcp\MovePageTool;
use App\Modules\Pages\Mcp\ReadPageTool;
use App\Modules\Pages\Mcp\TrashPageTool;
use App\Modules\Pages\Mcp\UpdatePageTool;
use App\Modules\Pages\Models\Page;
use App\Modules\Search\Mcp\SearchTool;
use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Mcp\CreateSpaceTool;
use App\Modules\Spaces\Mcp\ListSpacesTool;
use App\Modules\Spaces\Mcp\UpdateSpaceTool;
use App\Modules\Spaces\Models\Space;

beforeEach(function () {
    $this->editor = member(['name' => 'Max Mustermann']);
    $this->reader = member();
    $this->space = space([[$this->editor, SpaceRole::Editor], [$this->reader, SpaceRole::Reader]], ['name' => 'Handbuch']);
    $this->secret = space([], ['name' => 'Geheim']);
});

it('registers the tools of all modules', function () {
    KnowledgebaseServer::actingAs($this->editor)->tools()->assertRegistered([
        SearchTool::class, ListSpacesTool::class, CreateSpaceTool::class, UpdateSpaceTool::class,
        ListPagesTool::class, ReadPageTool::class, CreatePageTool::class, UpdatePageTool::class, MovePageTool::class, TrashPageTool::class,
    ]);
});

it('lists only readable spaces with the role', function () {
    KnowledgebaseServer::actingAs($this->reader)->tool(ListSpacesTool::class)
        ->assertOk()->assertSee(['"slug":"handbuch"', '"role":"reader"'])->assertDontSee('geheim');
});

it('searches only readable content and returns page ids', function () {
    $page = page($this->space, ['title' => 'Urlaub', 'content' => 'Urlaubsantrag stellen']);
    page($this->secret, ['title' => 'Gehalt', 'content' => 'Urlaubsantrag vertraulich']);

    KnowledgebaseServer::actingAs($this->reader)->tool(SearchTool::class, ['query' => 'urlaubsantrag'])
        ->assertOk()->assertSee(['"title":"Urlaub"', '"page_id":'.$page->id])->assertDontSee('Gehalt');
});

it('lists and reads pages, but reports foreign pages as not found', function () {
    $parent = page($this->space, ['title' => 'Eltern', 'content' => "# Hallo\n\nText", 'labels' => ['wichtig']]);
    page($this->space, ['title' => 'Kind'], $parent);
    $foreign = page($this->secret, ['title' => 'Intern']);

    KnowledgebaseServer::actingAs($this->reader)->tool(ListPagesTool::class, ['space' => 'handbuch'])->assertOk()->assertSee(['Eltern', 'Kind']);
    KnowledgebaseServer::actingAs($this->reader)->tool(ReadPageTool::class, ['id' => $parent->id])
        ->assertOk()->assertSee(['# Hallo', 'wichtig', '"version":1']);

    KnowledgebaseServer::actingAs($this->reader)->tool(ReadPageTool::class, ['id' => $foreign->id])->assertHasErrors(['Page not found.']);
    KnowledgebaseServer::actingAs($this->reader)->tool(ListPagesTool::class, ['space' => 'geheim'])->assertHasErrors(['Space not found.']);
});

it('creates pages only with the editor role', function () {
    KnowledgebaseServer::actingAs($this->reader)->tool(CreatePageTool::class, ['space' => 'handbuch', 'title' => 'Nein'])->assertHasErrors();

    KnowledgebaseServer::actingAs($this->editor)
        ->tool(CreatePageTool::class, ['space' => 'handbuch', 'title' => 'Von Claude', 'content' => "[TOC]\n\n## A", 'labels' => ['KI Notizen']])
        ->assertOk()->assertSee('"created":true');

    $page = Page::query()->where('title', 'Von Claude')->sole();
    expect($page->created_by)->toBe($this->editor->id);
    expect($page->labels->pluck('name')->all())->toBe(['ki-notizen']);
    expect(Activity::query()->where('action', 'page.created')->where('user_id', $this->editor->id)->exists())->toBeTrue();
});

it('updates pages with a new version and refuses stale versions', function () {
    $page = page($this->space, ['title' => 'A', 'content' => 'alt']);

    KnowledgebaseServer::actingAs($this->editor)->tool(UpdatePageTool::class, ['id' => $page->id, 'content' => 'neu', 'expected_version' => 1])
        ->assertOk()->assertSee('"version":2');
    KnowledgebaseServer::actingAs($this->editor)->tool(UpdatePageTool::class, ['id' => $page->id, 'content' => 'veraltet', 'expected_version' => 1])
        ->assertHasErrors();

    expect($page->fresh()->content)->toBe('neu');
    expect($page->versions()->count())->toBe(2);
    KnowledgebaseServer::actingAs($this->reader)->tool(UpdatePageTool::class, ['id' => $page->id, 'content' => 'x'])->assertHasErrors();
});

it('moves pages and protects against cycles', function () {
    $a = page($this->space, ['title' => 'A']);
    $b = page($this->space, ['title' => 'B'], $a);

    KnowledgebaseServer::actingAs($this->editor)->tool(MovePageTool::class, ['id' => $a->id, 'target_id' => $b->id, 'placement' => 'inside'])->assertHasErrors();
    KnowledgebaseServer::actingAs($this->editor)->tool(MovePageTool::class, ['id' => $b->id])->assertOk();

    expect($b->fresh()->parent_id)->toBeNull();
});

it('moves pages to the trash only as space admin', function () {
    $page = page($this->space, ['title' => 'Weg']);

    KnowledgebaseServer::actingAs($this->editor)->tool(TrashPageTool::class, ['id' => $page->id])->assertHasErrors();
    KnowledgebaseServer::actingAs(admin())->tool(TrashPageTool::class, ['id' => $page->id])->assertOk();

    expect($page->fresh()->trashed())->toBeTrue();
});

it('lets only administrators manage spaces', function () {
    KnowledgebaseServer::actingAs($this->editor)->tool(CreateSpaceTool::class, ['name' => 'Neu'])->assertHasErrors();

    $admin = admin();
    KnowledgebaseServer::actingAs($admin)->tool(CreateSpaceTool::class, ['name' => 'Projekte', 'icon' => '📁'])->assertOk()->assertSee('"slug":"projekte"');
    KnowledgebaseServer::actingAs($admin)->tool(UpdateSpaceTool::class, ['space' => 'projekte', 'description' => 'Alle Projekte'])->assertOk();

    expect(Space::query()->where('slug', 'projekte')->sole()->description)->toBe('Alle Projekte');
});

it('gives deactivated users nothing', function () {
    page($this->space, ['title' => 'Sichtbar']);
    $this->reader->forceFill(['is_active' => false])->save();

    KnowledgebaseServer::actingAs($this->reader)->tool(ListSpacesTool::class)->assertDontSee('handbuch');
});
