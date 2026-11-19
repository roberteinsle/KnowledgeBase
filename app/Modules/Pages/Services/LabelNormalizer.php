<?php

namespace App\Modules\Pages\Services;

use Illuminate\Support\Str;

final class LabelNormalizer
{
    /**
     * "Onboarding, HR Prozesse" → ["onboarding", "hr-prozesse"]
     *
     * @param  string|list<string>  $input
     * @return list<string>
     */
    public static function normalize(string|array $input): array
    {
        $parts = is_array($input) ? $input : explode(',', $input);

        return collect($parts)
            ->map(fn ($label) => Str::lower(trim((string) $label)))
            ->map(fn ($label) => preg_replace('/[^\p{L}\p{N}_-]+/u', '-', $label) ?? '')
            ->map(fn ($label) => trim(mb_substr($label, 0, 50), '-'))
            ->filter()
            ->unique()
            ->take(20)
            ->values()
            ->all();
    }
}
