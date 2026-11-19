<?php

namespace App\Modules\Pages\Services;

use App\Models\User;
use App\Modules\Pages\Models\Page;
use Illuminate\Support\Facades\DB;

final class FavoriteService
{
    /** @return bool true when the page is a favorite afterwards */
    public function toggle(Page $page, User $user): bool
    {
        $query = DB::table('page_favorites')->where('user_id', $user->id)->where('page_id', $page->id);

        if ($query->exists()) {
            $query->delete();

            return false;
        }

        DB::table('page_favorites')->insert(['user_id' => $user->id, 'page_id' => $page->id, 'created_at' => now()]);

        return true;
    }

    /** @return list<int> */
    public function pageIdsFor(User $user): array
    {
        return DB::table('page_favorites')->where('user_id', $user->id)->pluck('page_id')->map(fn ($id) => (int) $id)->all();
    }
}
