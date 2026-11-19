<?php

namespace App\Modules\Spaces\Enums;

/**
 * reader: read and export · editor: + create, edit, move, import, upload ·
 * admin: + delete pages, empty and restore the trash.
 */
enum SpaceRole: string
{
    case Reader = 'reader';
    case Editor = 'editor';
    case Admin = 'admin';

    public function level(): int
    {
        return match ($this) {
            self::Reader => 1,
            self::Editor => 2,
            self::Admin => 3,
        };
    }

    public function atLeast(self $role): bool
    {
        return $this->level() >= $role->level();
    }

    public function label(): string
    {
        return match ($this) {
            self::Reader => __('Reader'),
            self::Editor => __('Editor'),
            self::Admin => __('Space admin'),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $r) => [$r->value => $r->label()])->all();
    }
}
