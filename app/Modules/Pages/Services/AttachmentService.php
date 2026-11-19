<?php

namespace App\Modules\Pages\Services;

use App\Core\Activity\ActivityLogger;
use App\Models\User;
use App\Modules\Pages\Models\Attachment;
use App\Modules\Pages\Models\Page;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;

final class AttachmentService
{
    private const TEXT_EXTENSIONS = ['txt', 'md', 'markdown', 'csv'];

    private const MAX_INDEXED_TEXT = 200_000;

    public function __construct(private ActivityLogger $activity) {}

    public function storeUpload(Page $page, UploadedFile $file, User $user): Attachment
    {
        return $this->storeContents($page, $file->getClientOriginalName(), (string) file_get_contents($file->getRealPath()), $user);
    }

    public function storeContents(Page $page, string $filename, string $contents, User $user): Attachment
    {
        $name = $this->safeName($filename);
        $extension = Str::lower(pathinfo($name, PATHINFO_EXTENSION));
        $path = 'attachments/'.$page->id.'/'.Str::uuid().($extension !== '' ? '.'.$extension : '');

        $this->disk()->put($path, $contents);

        // Detected from content, not from the client.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: 'application/octet-stream';
        if ($mime === 'text/plain' && $extension !== '') {
            $mime = MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? $mime;
        }

        $attachment = new Attachment([
            'original_name' => $name,
            'path' => $path,
            'mime' => mb_substr($mime, 0, 100),
            'size' => strlen($contents),
            'text_content' => in_array($extension, self::TEXT_EXTENSIONS, true) ? $this->indexableText($contents) : null,
        ]);
        $attachment->page_id = $page->id;
        $attachment->uploaded_by = $user->id;
        $attachment->save();

        $this->activity->log('attachment.uploaded', $attachment, ['name' => $name, 'page_id' => $page->id], $user->id);

        return $attachment;
    }

    public function delete(Attachment $attachment, User $user): void
    {
        $this->deleteFile($attachment);
        $this->activity->log('attachment.deleted', $attachment, ['name' => $attachment->original_name], $user->id);
        $attachment->delete();
    }

    public function deleteFile(Attachment $attachment): void
    {
        $this->disk()->delete($attachment->path);
    }

    public function read(Attachment $attachment): ?string
    {
        return $this->disk()->get($attachment->path);
    }

    public function disk(): Filesystem
    {
        return Storage::disk(config('knowledgebase.attachments.disk'));
    }

    private function safeName(string $filename): string
    {
        $name = basename(str_replace('\\', '/', $filename));
        $name = preg_replace('/[\x00-\x1F\x7F"]/u', '', $name) ?? '';

        return mb_substr($name !== '' ? $name : 'file', 0, 255);
    }

    private function indexableText(string $contents): ?string
    {
        $text = mb_convert_encoding(mb_substr($contents, 0, self::MAX_INDEXED_TEXT), 'UTF-8', 'UTF-8');

        return str_replace("\0", '', $text) ?: null;
    }
}
