<?php

use App\Core\Http\LegacyRedirects;
use App\Modules\Spaces\Http\Controllers\SpaceController;
use Illuminate\Support\Facades\Route;

Route::get('/spaces', [SpaceController::class, 'index'])->name('spaces.index');

// Former German paths. Sub-paths below a space belong to other modules; the
// map only translates the fixed words, the space slug stays untouched.
LegacyRedirects::register('bereiche', 'spaces', [
    'seiten' => 'pages', 'neu' => 'new', 'papierkorb' => 'trash',
], keepFirst: true);
