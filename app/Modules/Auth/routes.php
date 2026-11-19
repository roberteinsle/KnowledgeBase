<?php

use App\Modules\Auth\Http\Controllers\AccountController;
use App\Modules\Auth\Http\Controllers\ConfirmController;
use App\Modules\Auth\Http\Controllers\LoginController;
use App\Modules\Auth\Http\Controllers\MagicLinkController;
use App\Modules\Auth\Http\Controllers\SecurityController;
use App\Modules\Auth\Http\Controllers\TwoFactorChallengeController;
use App\Modules\Auth\Services\MagicLinkService;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/anmelden', [LoginController::class, 'show'])->name('login');
    Route::post('/anmelden/link', [LoginController::class, 'requestLink'])
        ->middleware('throttle:magic-link-request')->name('login.request-link');
    Route::get('/anmelden/zwei-faktor', [TwoFactorChallengeController::class, 'show'])->name('two-factor.show');
    Route::post('/anmelden/zwei-faktor', [TwoFactorChallengeController::class, 'verify'])
        ->middleware('throttle:two-factor')->name('two-factor.verify');
});

Route::post('/sprache', [AccountController::class, 'guestLocale'])->name('locale.guest');

// Token format is checked by the route constraint before anything else runs;
// anything else is a plain 404. Not guest-only: also used for re-confirmation.
Route::get('/anmelden/link/{token}', [MagicLinkController::class, 'show'])
    ->where('token', MagicLinkService::TOKEN_PATTERN)->name('magic-link.show');
Route::post('/anmelden/link/{token}', [MagicLinkController::class, 'consume'])
    ->where('token', MagicLinkService::TOKEN_PATTERN)
    ->middleware('throttle:magic-link-consume')->name('magic-link.consume');

Route::middleware('auth')->group(function () {
    Route::post('/abmelden', [LoginController::class, 'logout'])->name('logout');

    // Named password.confirm so Laravel's (and the passkey package's) middleware use it.
    Route::get('/bestaetigen', [ConfirmController::class, 'show'])->name('password.confirm');
    Route::post('/bestaetigen/link', [ConfirmController::class, 'sendLink'])
        ->middleware('throttle:confirm-link')->name('password.confirm.link');

    Route::get('/konto/sicherheit', [SecurityController::class, 'show'])
        ->middleware('password.confirm')->name('account.security');
    Route::post('/konto/sicherheit/totp', [SecurityController::class, 'startTotp'])
        ->middleware('password.confirm')->name('account.totp.start');
    Route::post('/konto/sicherheit/totp/bestaetigen', [SecurityController::class, 'confirmTotp'])
        ->middleware(['password.confirm', 'throttle:two-factor'])->name('account.totp.confirm');
    Route::delete('/konto/sicherheit/totp', [SecurityController::class, 'disableTotp'])
        ->middleware('password.confirm')->name('account.totp.disable');
    Route::get('/konto/passkey', [SecurityController::class, 'passkeyOffer'])->name('account.passkey-offer');
    Route::post('/konto/passkey/spaeter', [SecurityController::class, 'dismissPasskeyOffer'])->name('account.passkey-offer.dismiss');

    Route::middleware('account')->group(function () {
        Route::get('/konto', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('/konto', [AccountController::class, 'update'])->name('account.update');
    });
});
