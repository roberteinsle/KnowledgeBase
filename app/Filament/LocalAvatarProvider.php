<?php

namespace App\Filament;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;

/**
 * Initials avatar as inline SVG. Filament's default loads ui-avatars.com –
 * an external request without consent, and blocked by our CSP anyway.
 */
class LocalAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $name = trim((string) ($record->getAttribute('name') ?? ''));
        $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
        $initials = mb_strtoupper(mb_substr($words[0], 0, 1).(isset($words[1]) ? mb_substr($words[1], 0, 1) : ''));

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="#0f172a"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="system-ui, sans-serif" font-size="26" fill="#fff">'
            .htmlspecialchars($initials, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            .'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
