<?php

namespace App\Modules\Auth\Services;

use App\Core\Activity\ActivityLogger;
use App\Models\User;
use App\Modules\Auth\Mail\MagicLinkMail;
use App\Modules\Auth\Models\MagicLink;
use App\Modules\Workspace\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

final class MagicLinkService
{
    public const TOKEN_PATTERN = '[0-9a-f]{64}';

    public function __construct(private ActivityLogger $activity) {}

    public static function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    public static function isWellFormedToken(string $token): bool
    {
        return preg_match('/\A'.self::TOKEN_PATTERN.'\z/', $token) === 1;
    }

    /**
     * Runs after the response has been sent (see LoginController), so the
     * caller never learns whether the address exists.
     */
    public function requestFor(string $email, ?string $redirect = null): void
    {
        $email = self::normalizeEmail($email);
        $user = User::query()->where('email', $email)->first() ?? $this->bootstrapAdmin($email);

        if ($user === null || ! $user->is_active) {
            return;
        }

        $this->issue($user, $redirect);
    }

    /**
     * Creates a link and mails it. Older open links of the same account stop working.
     */
    public function issue(User $user, ?string $redirect = null): void
    {
        $token = bin2hex(random_bytes(32));

        DB::transaction(function () use ($user, $token, $redirect) {
            MagicLink::query()->where('user_id', $user->id)->whereNull('used_at')->delete();

            $link = new MagicLink;
            $link->user_id = $user->id;
            $link->token_hash = hash('sha256', $token);
            $link->redirect_to = RedirectSanitizer::internalPath($redirect);
            $link->expires_at = now()->addMinutes((int) config('knowledgebase.magic_link.ttl_minutes'));
            $link->save();
        });

        // Host only from APP_URL, never from the request (host header poisoning).
        $url = rtrim((string) config('app.url'), '/').route('magic-link.show', ['token' => $token], false);

        try {
            Mail::to($user->email)
                ->locale($user->locale ?? Workspace::current()->default_locale)
                ->send(new MagicLinkMail($url, (int) config('knowledgebase.magic_link.ttl_minutes')));
        } catch (Throwable $e) {
            // Reported, but the request itself stays answered identically.
            Log::error('Magic link mail could not be sent.', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        $this->activity->log('auth.magic_link_requested', $user, userId: $user->id);
    }

    /**
     * Atomically redeems a token. Returns the user and the stored redirect, or null.
     *
     * @return array{user: User, redirect: string|null}|null
     */
    public function consume(string $token): ?array
    {
        if (! self::isWellFormedToken($token)) {
            return null;
        }

        $hash = hash('sha256', $token);

        // Single UPDATE so a double click cannot sign in twice.
        $redeemed = MagicLink::query()
            ->where('token_hash', $hash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);

        if ($redeemed !== 1) {
            return null;
        }

        $link = MagicLink::query()->where('token_hash', $hash)->first();
        $user = $link?->user;

        if ($link === null || $user === null || ! $user->is_active) {
            return null;
        }

        MagicLink::query()->where('user_id', $user->id)->whereKeyNot($link->id)->delete();

        return ['user' => $user, 'redirect' => $link->redirect_to];
    }

    private function bootstrapAdmin(string $email): ?User
    {
        $adminEmail = config('knowledgebase.admin_email');

        if (! is_string($adminEmail) || $adminEmail === '' || self::normalizeEmail($adminEmail) !== $email) {
            return null;
        }

        $user = new User(['name' => Str::before($email, '@'), 'email' => $email]);
        $user->forceFill(['role' => User::ROLE_ADMIN, 'is_active' => true])->save();

        $this->activity->log('user.bootstrapped_admin', $user, userId: $user->id);

        return $user;
    }
}
