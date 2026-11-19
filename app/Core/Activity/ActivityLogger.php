<?php

namespace App\Core\Activity;

use Illuminate\Database\Eloquent\Model;

final class ActivityLogger
{
    /** Keys whose values are never written to the log. */
    private const MASKED = ['token', 'secret', 'password', 'totp_secret', 'totp_recovery_codes', 'content', 'text_content', 'credential'];

    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $action, ?Model $subject = null, array $properties = [], ?int $userId = null): Activity
    {
        return Activity::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $this->mask($properties) ?: null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function mask(array $properties): array
    {
        foreach ($properties as $key => $value) {
            if (in_array($key, self::MASKED, true)) {
                $properties[$key] = '***';
            } elseif (is_array($value)) {
                $properties[$key] = $this->mask($value);
            }
        }

        return $properties;
    }
}
