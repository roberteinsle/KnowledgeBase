<?php

namespace App\Core\Account;

use App\Models\User;
use Illuminate\Contracts\View\View;

interface AccountSection
{
    public function key(): string;

    public function order(): int;

    public function render(User $user): ?View;
}
