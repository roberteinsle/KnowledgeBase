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
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Space extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'icon'];

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
