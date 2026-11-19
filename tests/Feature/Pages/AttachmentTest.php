<?php

use App\Modules\Pages\Models\Attachment;
use App\Modules\Pages\Services\AttachmentService;
use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Services\SpaceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->editor = member();
    $this->space = space([[$this->editor, SpaceRole::Editor]]);
    $this->page = page($this->space);
});

it('uploads attachments to the private disk', function () {
    $this->actingAs($this->editor)
        ->post(route('attachments.store', $this->page), ['files' => [UploadedFile::fake()->create('Handbuch.pdf', 10, 'application/pdf')]])
        ->assertRedirect();

    $attachment = Attachment::query()->sole();
    Storage::disk('local')->assertExists($attachment->path);
    expect($attachment->path)->toStartWith('attachments/');
});

it('rejects disallowed file types and oversized files', function () {
    $this->actingAs($this->editor)
        ->post(route('attachments.store', $this->page), ['files' => [UploadedFile::fake()->create('shell.php', 1, 'application/x-php')]])
        ->assertSessionHasErrors('files.0');

    $this->actingAs($this->editor)
        ->post(route('attachments.store', $this->page), ['files' => [UploadedFile::fake()->create('big.pdf', 30000, 'application/pdf')]])
        ->assertSessionHasErrors('files.0');

    expect(Attachment::count())->toBe(0);
});

it('serves attachments only to users who may read the page', function () {
    $attachment = app(AttachmentService::class)->storeContents($this->page, 'notiz.txt', 'Hallo', $this->editor);

    $this->actingAs($this->editor)->get(route('attachments.show', $attachment))->assertOk();
    $this->actingAs(member())->get(route('attachments.show', $attachment))->assertForbidden();
});

it('delivers only PDFs and raster images inline', function () {
    $service = app(AttachmentService::class);
    $png = $service->storeContents($this->page, 'bild.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='), $this->editor);
    $svg = $service->storeContents($this->page, 'bild.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', $this->editor);
    $html = $service->storeContents($this->page, 'seite.html', '<script>alert(1)</script>', $this->editor);

    $this->actingAs($this->editor)->get(route('attachments.show', $png))
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($this->get(route('attachments.show', $png))->headers->get('Content-Disposition'))->toStartWith('inline');

    foreach ([$svg, $html] as $attachment) {
        $response = $this->actingAs($this->editor)->get(route('attachments.show', $attachment));
        expect($response->headers->get('Content-Disposition'))->toStartWith('attachment');
        expect($response->headers->get('Content-Type'))->toBe('application/octet-stream');
    }
});

it('removes the file when an attachment is deleted', function () {
    $attachment = app(AttachmentService::class)->storeContents($this->page, 'notiz.txt', 'Hallo', $this->editor);

    $this->actingAs($this->editor)->delete(route('attachments.destroy', $attachment))->assertRedirect();

    Storage::disk('local')->assertMissing($attachment->path);
});

it('removes attachment files when a space is deleted', function () {
    $attachment = app(AttachmentService::class)->storeContents($this->page, 'notiz.txt', 'Hallo', $this->editor);

    app(SpaceService::class)->delete($this->space);

    Storage::disk('local')->assertMissing($attachment->path);
});
