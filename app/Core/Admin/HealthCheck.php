<?php

namespace App\Core\Admin;

interface HealthCheck
{
    public function key(): string;

    /** Untranslated label. */
    public function label(): string;

    public function order(): int;

    /** Must be cheap: runs on every dashboard view. Expensive checks belong in an explicit action. */
    public function run(): HealthResult;
}
