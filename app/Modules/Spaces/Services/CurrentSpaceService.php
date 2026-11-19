<?php

namespace App\Modules\Spaces\Services;

use App\Models\User;
use App\Modules\Spaces\Contracts\CurrentSpace;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Models\Space;
use Closure;
use Illuminate\Http\Request;

/**
 * Singleton for the resolvers; the per-request result lives in the request
 * attributes, so nothing leaks from one request into the next.
 */
final class CurrentSpaceService implements CurrentSpace
{
    private const ATTRIBUTE = 'spaces.current';

    /** @var list<Closure(Request): ?Space> */
    private array $resolvers = [];

    public function set(Space $space): void
    {
        request()->attributes->set(self::ATTRIBUTE, $space);
    }

    public function resolveUsing(Closure $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    /**
     * Only a space the request is actually about – pages outside a space
     * (home, spaces list, account …) have none, and the sidebar lists all spaces.
     */
    public function for(User $user): ?Space
    {
        $request = request();

        $explicit = $request->attributes->get(self::ATTRIBUTE);
        if ($explicit instanceof Space) {
            return $explicit;
        }

        foreach ($this->resolvers as $resolver) {
            $space = $resolver($request);
            if ($space instanceof Space && app(SpaceAccess::class)->roleFor($user, $space) !== null) {
                $this->set($space);

                return $space;
            }
        }

        return null;
    }
}
