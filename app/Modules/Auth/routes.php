<?php

use App\Core\Http\LegacyRedirects;
use App\Modules\Auth\Http\Controllers\AccountController;
use App\Modules\Auth\Http\Controllers\AvatarController;
use App\Modules\Auth\Http\Controllers\ConfirmController;
use App\Modules\Auth\Http\Controllers\LoginController;
use App\Modules\Auth\Http\Controllers\MagicLinkController;
use App\Modules\Auth\Http\Controllers\SecurityController;
use App\Modules\Auth\Http\Controllers\TwoFactorChallengeController;
use App\Modules\Auth\Services\MagicLinkService;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login/link', [LoginController::class, 'requestLink'])
        ->middleware('throttle:magic-link-request')->name('login.request-link');
    Route::get('/login/two-factor', [TwoFactorChallengeController::class, 'show'])->name('two-factor.show');
    Route::post('/login/two-factor', [TwoFactorChallengeController::class, 'verify'])
        ->middleware('throttle:two-factor')->name('two-factor.verify');
});

Route::post('/locale', [AccountController::class, 'guestLocale'])->name('locale.guest');

// Token format is checked by the route constraint before anything else runs;
// anything else is a plain 404. Not guest-only: also used for re-confirmation.
Route::get('/login/link/{token}', [MagicLinkController::class, 'show'])
    ->where('token', MagicLinkService::TOKEN_PATTERN)->name('magic-link.show');
Route::post('/login/link/{token}', [MagicLinkController::class, 'consume'])
    ->where('token', MagicLinkService::TOKEN_PATTERN)
    ->middleware('throttle:magic-link-consume')->name('magic-link.consume');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Named password.confirm so Laravel's (and the passkey package's) middleware use it.
    Route::get('/confirm', [ConfirmController::class, 'show'])->name('password.confirm');
    Route::post('/confirm/link', [ConfirmController::class, 'sendLink'])
        ->middleware('throttle:confirm-link')->name('password.confirm.link');

    Route::get('/account/security', [SecurityController::class, 'show'])
        ->middleware('password.confirm')->name('account.security');
    Route::post('/account/security/totp', [SecurityController::class, 'startTotp'])
        ->middleware('password.confirm')->name('account.totp.start');
    Route::post('/account/security/totp/confirm', [SecurityController::class, 'confirmTotp'])
        ->middleware(['password.confirm', 'throttle:two-factor'])->name('account.totp.confirm');
    Route::delete('/account/security/totp', [SecurityController::class, 'disableTotp'])
        ->middleware('password.confirm')->name('account.totp.disable');
    Route::get('/account/passkey', [SecurityController::class, 'passkeyOffer'])->name('account.passkey-offer');
    Route::post('/account/passkey/later', [SecurityController::class, 'dismissPasskeyOffer'])->name('account.passkey-offer.dismiss');

    Route::middleware('account')->group(function () {
        Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('/account', [AccountController::class, 'update'])->name('account.update');
        Route::post('/account/avatar', [AvatarController::class, 'store'])->middleware('throttle:uploads')->name('account.avatar.store');
        Route::delete('/account/avatar', [AvatarController::class, 'destroy'])->name('account.avatar.destroy');
        Route::get('/avatars/users/{user}', [AvatarController::class, 'show'])->whereNumber('user')->name('avatars.user');
    });
});

// Former German paths (bookmarks, sign-in links already sent).
LegacyRedirects::register('anmelden', 'login', ['zwei-faktor' => 'two-factor']);
LegacyRedirects::register('bestaetigen', 'confirm');
LegacyRedirects::register('konto', 'account', ['sicherheit' => 'security', 'spaeter' => 'later', 'bestaetigen' => 'confirm']);
