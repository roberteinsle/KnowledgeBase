<?php

namespace App\Core\Account;

use App\Models\User;
use Illuminate\Contracts\View\View;

/**
 * Additional sections on the account page (e.g. connected AI apps from the
 * MCP module), so the Auth module does not need to know other modules.
 */
final class AccountSectionRegistry
{
    /** @var array<string, AccountSection> */
    private array $sections = [];

    public function add(AccountSection $section): void
    {
        $this->sections[$section->key()] = $section;
    }

    /** @return list<View> */
    public function renderFor(User $user): array
    {
        $sections = array_values($this->sections);
        usort($sections, fn (AccountSection $a, AccountSection $b) => $a->order() <=> $b->order());

        return array_values(array_filter(array_map(fn (AccountSection $s) => $s->render($user), $sections)));
    }
}
