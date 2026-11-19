<?php

namespace App\Modules\Pages\Models;

use App\Models\User;
use App\Modules\Pages\Support\PageUrl;
use App\Modules\Spaces\Models\Space;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Content is Markdown; it is the single source for editing, import, export and search.
 *
 * @property int $id
 * @property int $space_id
 * @property int|null $parent_id
 * @property string $title
 * @property string $content
 * @property int $position
 * @property int $version
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Space $space
 */
class Page extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'content'];

    protected $hidden = ['search_vector'];

    protected $attributes = ['content' => '', 'position' => 0, 'version' => 1];

    /** Readable URL /<space>/<title>-<id>; loads the space if needed. */
    public function url(): string
    {
        return PageUrl::to($this->space->slug, $this->id, $this->title);
    }

    /** @return BelongsTo<Space, $this> */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /** @return BelongsTo<Page, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    /** @return HasMany<Page, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<PageVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class)->orderByDesc('version');
    }

    /** @return BelongsToMany<Label, $this> */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class)->orderBy('name');
    }

    /** @return HasMany<Attachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class)->orderBy('original_name');
    }

    /** @return BelongsToMany<User, $this> */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'page_favorites');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
