<?php

namespace App\Core\Admin;

final readonly class HealthResult
{
    public const OK = 'ok';

    public const WARNING = 'warning';

    public const ERROR = 'error';

    /** @param string $message already translated */
    public function __construct(public string $status, public string $message) {}

    public static function ok(string $message): self
    {
        return new self(self::OK, $message);
    }

    public static function warning(string $message): self
    {
        return new self(self::WARNING, $message);
    }

    public static function error(string $message): self
    {
        return new self(self::ERROR, $message);
    }
}
