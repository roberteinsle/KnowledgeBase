<?php

use App\Modules\Pages\Services\AttachmentService;
use App\Modules\Pages\Services\PageService;
use App\Modules\Spaces\Enums\SpaceRole;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = member();
    $this->space = space([[$this->user, SpaceRole::Editor]]);
});

it('replaces [TOC] with a table of contents of the headings', function (string $marker) {
    $page = page($this->space, ['content' => "{$marker}\n\n## Erste Schritte\n\n### Konto & Zugang\n\n## Fragen"]);

    $this->actingAs($this->user)->get($page->url())
        ->assertSee('<ul class="table-of-contents">', false)
        ->assertSee('<a href="#h-erste-schritte">Erste Schritte</a>', false)
        ->assertSee('<a href="#h-konto--zugang">Konto &amp; Zugang</a>', false)
        ->assertDontSee('[TOC]');
})->with(['[TOC]', '\[TOC\]', '[[_TOC_]]']);

it('shows an "On this page" outline for longer pages only', function () {
    $long = page($this->space, ['content' => "## Eins\n\n## Zwei\n\n### Zwei a\n\n## Drei"]);
    $short = page($this->space, ['content' => '## Nur eine']);

    $this->actingAs($this->user)->get($long->url())->assertSee(__('On this page'))->assertSee('href="#h-zwei-a"', false);
    $this->actingAs($this->user)->get($short->url())->assertDontSee(__('On this page'));
});

it('renders the live preview with the page renderer', function () {
    $this->actingAs($this->user)
        ->postJson(route('pages.preview'), ['content' => "[TOC]\n\n## Abschnitt\n\n<script>alert(1)</script>"])
        ->assertOk()
        ->assertJsonPath('html', fn (string $html) => str_contains($html, 'table-of-contents')
            && str_contains($html, '&lt;script&gt;')
            && ! str_contains($html, '<script>'));
});

it('limits and protects the preview', function () {
    $this->postJson(route('pages.preview'), ['content' => 'x'])->assertUnauthorized();
    $this->actingAs($this->user)->postJson(route('pages.preview'), ['content' => str_repeat('a', 1000001)])->assertUnprocessable();
});

it('exports a page as standalone HTML with embedded images', function () {
    Storage::fake('local');
    $page = page($this->space, ['title' => 'Export <Test>']);
    $image = app(AttachmentService::class)->storeContents($page, 'bild.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='), $this->user);
    app(PageService::class)->update($page, $page->title, "[TOC]\n\n## Bild\n\n![Logo](/attachments/{$image->id})\n\n<b>roh</b>", $this->user);

    $response = $this->actingAs($this->user)->get(route('export.page', [$page, 'page-html']))->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('attachment')->toContain('.html');
    expect($response->getContent())
        ->toStartWith('<!DOCTYPE html>')
        ->toContain('<title>Export &lt;Test&gt;</title>')
        ->toContain('src="data:image/png;base64,')
        ->toContain('table-of-contents')
        ->toContain('&lt;b&gt;roh&lt;/b&gt;')
        ->not->toContain('<b>roh</b>');
});

it('offers the HTML export in the page menu', function () {
    $page = page($this->space);

    $this->actingAs($this->user)->get($page->url())->assertSee(route('export.page', [$page, 'page-html']), false);
});
