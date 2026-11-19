<?php

namespace App\Modules\Mcp\Account;

use App\Core\Account\AccountSection;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Laravel\Passport\Token;

/** "Connected AI apps" on the account page: clients holding valid tokens. */
class ConnectedAppsSection implements AccountSection
{
    public function key(): string
    {
        return 'mcp-connections';
    }

    public function order(): int
    {
        return 50;
    }

    public function render(User $user): ?View
    {
        $connections = Token::query()
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->where('expires_at', '>', now()->subDays(30)) // refresh tokens keep them connected
            ->with('client')
            ->latest('created_at')
            ->get()
            ->unique('client_id')
            ->values();

        return view('mcp.connections', ['connections' => $connections, 'endpoint' => url('/mcp')]);
    }
}
