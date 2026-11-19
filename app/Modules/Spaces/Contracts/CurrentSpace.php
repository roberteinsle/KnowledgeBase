<?php

namespace App\Modules\Spaces\Contracts;

use App\Models\User;
use App\Modules\Spaces\Models\Space;
use Closure;
use Illuminate\Http\Request;

/**
 * The space the request is about: explicitly set, or found by a resolver from
 * the current route (modules register resolvers for their own route
 * parameters). Null outside a space (home, spaces list, account …). Drives
 * the sidebar.
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
