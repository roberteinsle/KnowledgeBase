<?php

namespace App\Modules\Mcp\Http\Controllers;

use App\Core\Activity\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

class ConnectionController
{
    /** Revokes all access and refresh tokens of one client for the current user. */
    public function destroy(Request $request, string $client, ActivityLogger $activity): RedirectResponse
    {
        $tokens = Token::query()->where('user_id', $request->user()->id)->where('client_id', $client)->get();
        abort_if($tokens->isEmpty(), 404);

        foreach ($tokens as $token) {
            $token->forceFill(['revoked' => true])->save();
            RefreshToken::query()->where('access_token_id', $token->id)->update(['revoked' => true]);
        }

        $activity->log('mcp.connection_revoked', $request->user(), ['client' => $tokens->first()->client?->name]);

        return back()->with('status', __('Access revoked.'));
    }
}
