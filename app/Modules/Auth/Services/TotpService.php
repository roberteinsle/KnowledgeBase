<?php

namespace App\Modules\Auth\Services;

use App\Core\Activity\ActivityLogger;
use App\Models\User;
use App\Modules\Workspace\Models\Workspace;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

final class TotpService
{
    public function __construct(
        private Google2FA $google2fa,
        private ActivityLogger $activity,
    ) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function qrCodeSvg(User $user, string $secret): string
    {
        $uri = $this->google2fa->getQRCodeUrl(Workspace::current()->name, $user->email, $secret);

        return (new Writer(new ImageRenderer(new RendererStyle(192, 1), new SvgImageBackEnd)))->writeString($uri);
    }

    public function verify(User $user, string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (preg_match('/\A\d{6}\z/', $code) !== 1) {
            return false;
        }

        // A code is accepted only once (replay within its validity window).
        $replayKey = 'totp-used:'.$user->id.':'.$code;
        if (Cache::has($replayKey)) {
            return false;
        }

        if (! $this->google2fa->verifyKey($secret, $code, 1)) {
            return false;
        }

        Cache::put($replayKey, true, now()->addSeconds(120));

        return true;
    }

    /**
     * @return list<string> the plain recovery codes, shown once
     */
    public function enable(User $user, string $secret): array
    {
        $codes = $this->newRecoveryCodes();

        $user->forceFill([
            'totp_secret' => $secret,
            'totp_recovery_codes' => $codes,
            'totp_confirmed_at' => now(),
        ])->save();

        $this->activity->log('auth.totp_enabled', $user);

        return $codes;
    }

    public function disable(User $user): void
    {
        $user->forceFill(['totp_secret' => null, 'totp_recovery_codes' => null, 'totp_confirmed_at' => null])->save();
        $this->activity->log('auth.totp_disabled', $user);
    }

    /** Verifies a TOTP code or burns a recovery code. */
    public function verifyChallenge(User $user, string $input): bool
    {
        if (! $user->hasTotp()) {
            return false;
        }

        if ($this->verify($user, (string) $user->totp_secret, $input)) {
            return true;
        }

        $input = Str::upper(trim($input));
        $codes = $user->totp_recovery_codes ?? [];

        foreach ($codes as $index => $code) {
            if (hash_equals($code, $input)) {
                unset($codes[$index]);
                $user->forceFill(['totp_recovery_codes' => array_values($codes)])->save();
                $this->activity->log('auth.recovery_code_used', $user, ['remaining' => count($codes)]);

                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function newRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::upper(Str::random(5).'-'.Str::random(5)))
            ->all();
    }
}
