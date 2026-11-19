<?php

namespace App\Modules\Pages\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 */
class Label extends Model
{
    protected $fillable = ['name'];

    public function getRouteKeyName(): string
    {
        return 'name';
    }

    /** @return BelongsToMany<Page, $this> */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class);
    }
}
