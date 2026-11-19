<?php

namespace App\Modules\Groups\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $system_key
 */
class Group extends Model
{
    public const EVERYONE = 'everyone';

    protected $fillable = ['name', 'description'];

    public function isEveryone(): bool
    {
        return $this->system_key === self::EVERYONE;
    }

    public static function everyone(): self
    {
        return self::query()->where('system_key', self::EVERYONE)->firstOrFail();
    }

    /**
     * Explicit members. "Everyone" has none stored: all users belong to it.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
