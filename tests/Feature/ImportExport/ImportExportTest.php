<?php

use App\Modules\Pages\Models\Attachment;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\AttachmentService;
use App\Modules\Pages\Services\PageService;
use App\Modules\Spaces\Enums\SpaceRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->editor = member();
    $this->space = space([[$this->editor, SpaceRole::Editor]]);
});

function zipFile(array $files, string $name = 'export.zip'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'zip');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    foreach ($files as $file => $contents) {
        $zip->addFromString($file, $contents);
    }
    $zip->close();

    return new UploadedFile($path, $name, 'application/zip', null, true);
}

it('imports pasted Markdown and takes the title from the first heading', function () {
    $this->actingAs($this->editor)->post(route('import.store', $this->space), [
        'mode' => 'paste',
        'markdown' => "# Reiserichtlinie\n\nGilt ab sofort.",
    ])->assertRedirect();

    $page = Page::query()->sole();
    expect($page->title)->toBe('Reiserichtlinie');
    expect($page->content)->toBe('Gilt ab sofort.');
});

it('prefers an explicit title for pasted Markdown', function () {
    $this->actingAs($this->editor)->post(route('import.store', $this->space), [
        'mode' => 'paste', 'title' => 'Eigener Titel', 'markdown' => "# Überschrift\n\nText",
    ]);

    expect(Page::query()->sole()->title)->toBe('Eigener Titel');
});

it('imports a Markdown file with front matter', function () {
    $file = UploadedFile::fake()->createWithContent('notiz.md', "---\ntitle: \"Team-Regeln\"\ntags: [team, regeln]\n---\nInhalt");

    $this->actingAs($this->editor)->post(route('import.store', $this->space), ['mode' => 'file', 'file' => $file])->assertRedirect();

    $page = Page::query()->sole();
    expect($page->title)->toBe('Team-Regeln');
    expect($page->labels->pluck('name')->all())->toBe(['regeln', 'team']);
});

it('imports a ZIP with folders, images and links between pages', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    $file = zipFile([
        'Handbuch.md' => "# Handbuch\n\nSiehe [Urlaub](Handbuch/Urlaub.md) und ![Logo](bilder/logo.png)",
        'Handbuch/Urlaub.md' => "# Urlaub\n\nZurück zum [Handbuch](../Handbuch.md). [[Handbuch|Start]]",
        'Handbuch/Krankheit 0123456789abcdef0123456789abcdef.md' => 'Krankmeldung bis 9 Uhr.',
        'bilder/logo.png' => $png,
        '../../etc/evil.md' => 'nope',
        '__MACOSX/._Handbuch.md' => 'meta',
    ]);

    $this->actingAs($this->editor)->post(route('import.store', $this->space), ['mode' => 'file', 'file' => $file])->assertRedirect();

    expect(Page::count())->toBe(3);
    $root = Page::query()->where('title', 'Handbuch')->sole();
    $vacation = Page::query()->where('title', 'Urlaub')->sole();
    $sick = Page::query()->where('title', 'Krankheit')->sole();

    expect($vacation->parent_id)->toBe($root->id);
    expect($sick->parent_id)->toBe($root->id);

    $attachment = Attachment::query()->sole();
    expect($attachment->page_id)->toBe($root->id);
    expect($root->content)->toContain('(/pages/'.$vacation->id.')')->toContain('(/attachments/'.$attachment->id.')');
    expect($vacation->content)->toContain('(/pages/'.$root->id.')')->toContain('[Start](/pages/'.$root->id.')');
    expect($root->versions()->count())->toBe(1);
});

it('rejects archives without Markdown and unsupported files', function () {
    $this->actingAs($this->editor)->post(route('import.store', $this->space), [
        'mode' => 'file', 'file' => zipFile(['bild.png' => 'x']),
    ])->assertSessionHasErrors('file');

    $this->actingAs($this->editor)->post(route('import.store', $this->space), [
        'mode' => 'file', 'file' => UploadedFile::fake()->create('virus.exe', 1),
    ])->assertSessionHasErrors('file');

    expect(Page::count())->toBe(0);
});

it('does not let readers import', function () {
    $reader = member();
    $space = space([[$reader, SpaceRole::Reader]]);

    $this->actingAs($reader)->post(route('import.store', $space), ['mode' => 'paste', 'markdown' => '# X'])->assertForbidden();
});

it('enforces the daily import limit in the domain logic', function () {
    config(['knowledgebase.import.max_per_user_per_day' => 2]);

    foreach (range(1, 2) as $i) {
        $this->actingAs($this->editor)->post(route('import.store', $this->space), ['mode' => 'paste', 'markdown' => "# Seite {$i}"])->assertSessionHasNoErrors();
    }

    $this->actingAs($this->editor)->post(route('import.store', $this->space), ['mode' => 'paste', 'markdown' => '# Zu viel'])->assertSessionHasErrors('file');
});

it('exports a page as Markdown with front matter', function () {
    $page = page($this->space, ['title' => 'Export "Test"', 'content' => 'Inhalt', 'labels' => ['a']]);

    $response = $this->actingAs($this->editor)->get(route('export.page', [$page, 'page-md']))->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->getContent())->toContain('title: "Export \\"Test\\""')->toContain("tags:\n  - \"a\"")->toContain('Inhalt');
});

it('returns 404 for unknown export formats', function () {
    $page = page($this->space);

    $this->actingAs($this->editor)->get(route('export.page', [$page, 'nope']))->assertNotFound();
    $this->actingAs($this->editor)->get(route('export.page', [$page, 'space-zip']))->assertNotFound();
});

it('exports a space as ZIP that imports again with the same structure', function () {
    $root = page($this->space, ['title' => 'Wurzel', 'content' => 'Oben']);
    $child = page($this->space, ['title' => 'Kind', 'content' => 'Zur [Wurzel](/pages/'.$root->id.')'], $root);
    $attachment = app(AttachmentService::class)->storeContents($child, 'liste.txt', 'Inhalt', $this->editor);
    app(PageService::class)->update($child, 'Kind', $child->content."\n\n[Liste](/attachments/{$attachment->id})", $this->editor);

    $response = $this->actingAs($this->editor)->get(route('export.space', [$this->space, 'space-zip']))->assertOk();
    $zipPath = $response->baseResponse->getFile()->getPathname();
    $copy = tempnam(sys_get_temp_dir(), 'zip');
    copy($zipPath, $copy);

    $zip = new ZipArchive;
    $zip->open($copy);
    expect($zip->locateName('Wurzel.md'))->not->toBeFalse();
    expect($zip->locateName('Wurzel/Kind.md'))->not->toBeFalse();
    expect($zip->getFromName('Wurzel/Kind.md'))->toContain('(<../Wurzel.md>)')->toContain('_attachments/');
    $zip->close();

    $target = space([[$this->editor, SpaceRole::Editor]]);
    $this->actingAs($this->editor)->post(route('import.store', $target), [
        'mode' => 'file', 'file' => new UploadedFile($copy, 'backup.zip', 'application/zip', null, true),
    ])->assertSessionHasNoErrors();

    $newRoot = Page::query()->where('space_id', $target->id)->where('title', 'Wurzel')->sole();
    $newChild = Page::query()->where('space_id', $target->id)->where('title', 'Kind')->sole();
    expect($newChild->parent_id)->toBe($newRoot->id);
    expect($newChild->content)->toContain('/pages/'.$newRoot->id);
    expect($newChild->attachments()->count())->toBe(1);
});
