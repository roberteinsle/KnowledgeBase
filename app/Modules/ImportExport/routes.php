<?php

use App\Modules\ImportExport\Http\Controllers\ExportController;
use App\Modules\ImportExport\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::get('/bereiche/{space}/import', [ImportController::class, 'create'])->name('import.create');
Route::post('/bereiche/{space}/import', [ImportController::class, 'store'])
    ->middleware('throttle:import')->name('import.store');

Route::get('/seiten/{page}/export/{format}', [ExportController::class, 'page'])
    ->whereNumber('page')->where('format', '[a-z-]+')->middleware('throttle:export')->name('export.page');
Route::get('/bereiche/{space}/export/{format}', [ExportController::class, 'space'])
    ->where('format', '[a-z-]+')->middleware('throttle:export')->name('export.space');
