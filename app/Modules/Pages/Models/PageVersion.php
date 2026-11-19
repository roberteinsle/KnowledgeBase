<?php

namespace App\Modules\Pages\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $page_id
 * @property int $version
 * @property string $title
 * @property string $content
 * @property int|null $user_id
 * @property Carbon $created_at
 */
class PageVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['version', 'title', 'content', 'user_id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
