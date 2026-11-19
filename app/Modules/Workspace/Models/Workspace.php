<?php

namespace App\Modules\Workspace\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The single top-level entity of an installation. Exactly one row exists
 * (created by the migration).
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $default_locale
 * @property bool $enforce_mfa
 */
class Workspace extends Model
{
    protected $fillable = ['name', 'description', 'default_locale', 'enforce_mfa'];

    protected function casts(): array
    {
        return ['enforce_mfa' => 'boolean'];
    }

    /** Loaded once per request (scoped binding in WorkspaceServiceProvider). */
    public static function current(): self
    {
        return app(self::class.'@current');
    }

    public static function forgetCurrent(): void
    {
        app()->forgetInstance(self::class.'@current');
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::forgetCurrent());
    }
}
