<?php

namespace App\Core\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Stored in UTC (timestamptz), always displayed in the configured display timezone.
 */
final class Dates
{
    public static function display(?DateTimeInterface $date, string $format = 'datetime'): string
    {
        if ($date === null) {
            return '';
        }

        $local = Carbon::instance($date)->setTimezone(config('knowledgebase.display_timezone'))->locale(app()->getLocale());

        return match ($format) {
            'date' => $local->isoFormat('L'),
            'relative' => $local->diffForHumans(),
            default => $local->isoFormat('L LT'),
        };
    }
}
