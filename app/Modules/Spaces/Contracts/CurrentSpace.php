<?php

namespace App\Modules\Spaces\Contracts;

use App\Models\User;
use App\Modules\Spaces\Models\Space;
use Closure;
use Illuminate\Http\Request;

/**
 * The space the user is working in: explicitly set, or found by a resolver
 * from the current route (modules register resolvers for their own route
 * parameters), otherwise the last visited readable space, otherwise the first
 * readable space. Drives the sidebar.
 */
interface CurrentSpace
{
    public function set(Space $space): void;

    /**
     * @param  Closure(Request): ?Space  $resolver
     */
    public function resolveUsing(Closure $resolver): void;

    public function for(User $user): ?Space;
}
