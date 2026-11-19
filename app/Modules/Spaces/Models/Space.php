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
        'account', 'admin', 'api', 'assets', 'attachments', 'build', 'confirm', 'css', 'docs', 'filament', 'fonts',
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

    /** Two letters for the fallback badge, e.g. "Human Resources" → "HR". */
    public function initials(): string
    {
        $words = preg_split('/[\s\-_]+/u', trim($this->name), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
        $letters = count($words) > 1
            ? mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1)
            : mb_substr($words[0], 0, 2);

        return mb_strtoupper($letters);
    }

    /** Stable colour for the initials badge. */
    public function badgeColor(): string
    {
        $colors = ['bg-red-700', 'bg-purple-700', 'bg-indigo-700', 'bg-sky-700', 'bg-teal-700', 'bg-emerald-700', 'bg-amber-700', 'bg-rose-700', 'bg-slate-700'];

        return $colors[crc32($this->name) % count($colors)];
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
