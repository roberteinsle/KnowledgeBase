<?php

use App\Modules\Spaces\Http\Controllers\SpaceController;
use Illuminate\Support\Facades\Route;

Route::get('/bereiche', [SpaceController::class, 'index'])->name('spaces.index');
