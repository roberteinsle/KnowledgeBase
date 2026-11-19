<?php

return [

    // First administrator. Signing in with this address creates the account
    // (or promotes it) — the address is never hard-coded.
    'admin_email' => env('ADMIN_EMAIL'),

    'locales' => [
        'de' => 'Deutsch',
        'en' => 'English',
        'fr' => 'Français',
        'it' => 'Italiano',
        'es' => 'Español',
    ],

    'display_timezone' => env('DISPLAY_TIMEZONE', 'Europe/Berlin'),

    'magic_link' => [
        'ttl_minutes' => 15,
    ],

    'attachments' => [
        'disk' => 'local',
        'max_kb' => 20480,
        'mimes' => ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'txt', 'md', 'csv', 'zip', 'docx', 'xlsx', 'pptx', 'odt', 'ods'],
        // Only these are shown inline; everything else is a download.
        'inline_mimes' => ['application/pdf', 'image/png', 'image/jpeg', 'image/gif', 'image/webp'],
    ],

    'import' => [
        'max_kb' => 20480,
        'max_paste_chars' => 500000,
        'max_documents' => 500,
        'max_uncompressed_mb' => 50,
        // Cost limit in the domain logic, independent of the route throttle.
        'max_per_user_per_day' => 50,
    ],

];
