<?php

namespace App\Modules\Spaces\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $icon_path
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Space extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'icon'];

    /**
     * Page URLs are /<space-slug>/<title>-<id>, so a space slug must never be a
     * first path segment the system uses itself (including former German paths).
     */
    public const RESERVED_SLUGS = [
        'account', 'admin', 'api', 'assets', 'attachments', 'avatars', 'build', 'confirm', 'css', 'docs', 'filament', 'fonts',
        'help', 'js', 'labels', 'livewire', 'locale', 'login', 'logout', 'new', 'pages', 'passkeys', 'search',
        'spaces', 'storage', 'trash', 'up', 'user', 'vendor',
        'abmelden', 'anhaenge', 'anmelden', 'bereiche', 'bestaetigen', 'konto', 'papierkorb', 'seiten', 'sprache', 'suche',
    ];

    /** Route constraint for a space slug in page URLs (excludes the reserved words). */
    public static function routePattern(): string
    {
        return '(?!(?:'.implode('|', self::RESERVED_SLUGS).')$)[a-z0-9]+(?:-[a-z0-9]+)*';
    }

    public function hasImage(): bool
    {
        return $this->icon_path !== null && $this->icon_path !== '';
    }

    /** Icon image URL (upload, else generated avatar with emoji or initials). */
    public function iconUrl(): string
    {
        return route('spaces.icon', $this).'?v='.$this->updated_at->timestamp;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<SpacePermission, $this> */
    public function permissions(): HasMany
    {
        return $this->hasMany(SpacePermission::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
