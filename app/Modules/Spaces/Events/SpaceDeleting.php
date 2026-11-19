<?php

namespace App\Modules\Spaces\Events;

use App\Modules\Spaces\Models\Space;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired before a space and (via foreign keys) all its content is deleted.
 * Modules that keep files or external state for a space clean up here.
 */
final class SpaceDeleting
{
    use Dispatchable;

    public function __construct(public readonly Space $space) {}
}
