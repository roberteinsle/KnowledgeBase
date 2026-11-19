<?php

namespace App\Core\Support;

final class Locales
{
    /** @return array<string, string> locale => native name */
    public static function all(): array
    {
        return config('knowledgebase.locales');
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, self::all());
    }
}
