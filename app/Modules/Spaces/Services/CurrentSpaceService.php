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
    private const SESSION_KEY = 'spaces.last_space_id';

    private const ATTRIBUTE = 'spaces.current';

    /** @var list<Closure(Request): ?Space> */
    private array $resolvers = [];

    public function set(Space $space): void
    {
        $request = request();
        $request->attributes->set(self::ATTRIBUTE, $space);

        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $space->id);
        }
    }

    public function resolveUsing(Closure $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    public function for(User $user): ?Space
    {
        $request = request();
        $access = app(SpaceAccess::class);

        $explicit = $request->attributes->get(self::ATTRIBUTE);
        if ($explicit instanceof Space) {
            return $explicit;
        }

        foreach ($this->resolvers as $resolver) {
            $space = $resolver($request);
            if ($space instanceof Space && $access->roleFor($user, $space) !== null) {
                $this->set($space);

                return $space;
            }
        }

        $readable = $access->spaceIdsFor($user);
        if ($readable === []) {
            return null;
        }

        $last = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;
        $space = in_array($last, $readable, true)
            ? Space::query()->find($last)
            : Space::query()->whereIn('id', $readable)->orderBy('name')->first();

        if ($space !== null) {
            $request->attributes->set(self::ATTRIBUTE, $space);
        }

        return $space;
    }
}
