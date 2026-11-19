<?php

namespace App\Modules\Spaces\Models;

use App\Modules\Spaces\Enums\SpaceRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $space_id
 * @property string $subject_type 'user' | 'group'
 * @property int $subject_id
 * @property SpaceRole $role
 */
class SpacePermission extends Model
{
    protected $fillable = ['subject_type', 'subject_id', 'role'];

    protected function casts(): array
    {
        return ['role' => SpaceRole::class];
    }

    /** @return BelongsTo<Space, $this> */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
