<?php

namespace App\Models;

use App\Modules\Groups\Models\Group;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property string|null $locale
 * @property bool $is_active
 * @property string|null $totp_secret
 * @property list<string>|null $totp_recovery_codes
 * @property Carbon|null $totp_confirmed_at
 * @property Carbon|null $last_login_at
 * @property Carbon $created_at
 */
class User extends Authenticatable implements FilamentUser, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MEMBER = 'member';

    /** Role, activation and 2FA fields are internal: set them with forceFill(). */
    protected $fillable = ['name', 'email', 'locale'];

    protected $hidden = ['remember_token', 'totp_secret', 'totp_recovery_codes'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'totp_secret' => 'encrypted',
            'totp_recovery_codes' => 'encrypted:array',
            'totp_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** E-mail addresses are stored trimmed and lower case; lookups rely on it. */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value) => mb_strtolower(trim($value)));
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function hasTotp(): bool
    {
        return $this->totp_confirmed_at !== null && $this->totp_secret !== null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->isAdmin();
    }

    /** @return BelongsToMany<Group, $this> */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }
}
