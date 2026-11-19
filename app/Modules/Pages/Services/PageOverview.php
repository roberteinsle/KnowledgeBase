<?php

namespace App\Modules\Pages\Services;

use App\Models\User;
use App\Modules\Pages\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The three lists on the home page and the space overview:
 * recently updated, the user's favorites, pages the user created.
 */
final class PageOverview
{
    /**
     * @param  list<int>  $spaceIds  already filtered by the user's rights
     * @return array{recent: Collection<int, Page>, favorites: Collection<int, Page>, mine: Collection<int, Page>}
     */
    public function for(User $user, array $spaceIds, int $limit = 15): array
    {
        $base = fn (): Builder => Page::query()
            ->whereIn('space_id', $spaceIds)
            ->with(['space:id,name,slug,icon,icon_path,updated_at', 'editor:id,name']);

        return [
            'recent' => $base()->latest('updated_at')->limit($limit)->get(),
            'favorites' => $base()
                ->whereHas('favoritedBy', fn (Builder $q) => $q->whereKey($user->id))
                ->orderBy('title')->limit($limit)->get(),
            'mine' => $base()->where('created_by', $user->id)->latest('updated_at')->limit($limit)->get(),
        ];
    }
}
