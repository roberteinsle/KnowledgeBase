<?php

use App\Modules\ImportExport\Http\Controllers\ExportController;
use App\Modules\ImportExport\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::get('/spaces/{space}/import', [ImportController::class, 'create'])->name('import.create');
Route::post('/spaces/{space}/import', [ImportController::class, 'store'])
    ->middleware('throttle:import')->name('import.store');

Route::get('/pages/{page}/export/{format}', [ExportController::class, 'page'])
    ->whereNumber('page')->where('format', '[a-z-]+')->middleware('throttle:export')->name('export.page');
Route::get('/spaces/{space}/export/{format}', [ExportController::class, 'space'])
    ->where('format', '[a-z-]+')->middleware('throttle:export')->name('export.space');
