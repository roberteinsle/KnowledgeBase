<?php

namespace App\Modules\Pages\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Stored on the private disk, delivered only through AttachmentController.
 *
 * @property int $id
 * @property int $page_id
 * @property string $original_name
 * @property string $path
 * @property string $mime
 * @property int $size
 * @property string|null $text_content
 * @property int|null $uploaded_by
 * @property Carbon $created_at
 * @property-read Page $page
 */
class Attachment extends Model
{
    protected $fillable = ['original_name', 'path', 'mime', 'size', 'text_content'];

    protected $hidden = ['search_vector', 'text_content', 'path'];

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/') && in_array($this->mime, config('knowledgebase.attachments.inline_mimes'), true);
    }

    public function url(): string
    {
        return route('attachments.show', $this);
    }
}
