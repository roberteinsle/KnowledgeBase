<?php

use App\Modules\Pages\Http\Controllers\AttachmentController;
use App\Modules\Pages\Http\Controllers\LabelController;
use App\Modules\Pages\Http\Controllers\PageController;
use App\Modules\Pages\Http\Controllers\PageVersionController;
use App\Modules\Pages\Http\Controllers\SpaceHomeController;
use App\Modules\Pages\Http\Controllers\TrashController;
use Illuminate\Support\Facades\Route;

Route::get('/bereiche/{space}', [SpaceHomeController::class, 'show'])->name('spaces.show');
Route::get('/bereiche/{space}/seiten/neu', [PageController::class, 'create'])->name('pages.create');
Route::post('/bereiche/{space}/seiten', [PageController::class, 'store'])->name('pages.store');
Route::get('/bereiche/{space}/papierkorb', [TrashController::class, 'index'])->name('trash.index');

Route::get('/seiten/{page}', [PageController::class, 'show'])->whereNumber('page')->name('pages.show');
Route::get('/seiten/{page}/bearbeiten', [PageController::class, 'edit'])->whereNumber('page')->name('pages.edit');
Route::put('/seiten/{page}', [PageController::class, 'update'])->whereNumber('page')->name('pages.update');
Route::get('/seiten/{page}/verschieben', [PageController::class, 'moveForm'])->whereNumber('page')->name('pages.move');
Route::put('/seiten/{page}/verschieben', [PageController::class, 'move'])->whereNumber('page')->name('pages.move.update');
Route::delete('/seiten/{page}', [PageController::class, 'destroy'])->whereNumber('page')->name('pages.destroy');

Route::get('/seiten/{page}/versionen', [PageVersionController::class, 'index'])->whereNumber('page')->name('pages.versions');
Route::get('/seiten/{page}/versionen/{version}', [PageVersionController::class, 'show'])->whereNumber(['page', 'version'])->name('pages.versions.show');
Route::post('/seiten/{page}/versionen/{version}/wiederherstellen', [PageVersionController::class, 'restore'])->whereNumber(['page', 'version'])->name('pages.versions.restore');

Route::post('/seiten/{page}/anhaenge', [AttachmentController::class, 'store'])->whereNumber('page')
    ->middleware('throttle:uploads')->name('attachments.store');
Route::get('/anhaenge/{attachment}', [AttachmentController::class, 'show'])->whereNumber('attachment')->name('attachments.show');
Route::delete('/anhaenge/{attachment}', [AttachmentController::class, 'destroy'])->whereNumber('attachment')->name('attachments.destroy');

Route::post('/papierkorb/{page}/wiederherstellen', [TrashController::class, 'restore'])->whereNumber('page')->withTrashed()->name('trash.restore');
Route::delete('/papierkorb/{page}', [TrashController::class, 'destroy'])->whereNumber('page')->withTrashed()->name('trash.destroy');

Route::get('/labels/{label}', [LabelController::class, 'show'])->name('labels.show');
