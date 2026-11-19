<?php

use App\Core\Http\LegacyRedirects;
use App\Modules\Pages\Http\Controllers\AttachmentController;
use App\Modules\Pages\Http\Controllers\FavoriteController;
use App\Modules\Pages\Http\Controllers\LabelController;
use App\Modules\Pages\Http\Controllers\PageController;
use App\Modules\Pages\Http\Controllers\PageCopyController;
use App\Modules\Pages\Http\Controllers\PageTreeController;
use App\Modules\Pages\Http\Controllers\PageVersionController;
use App\Modules\Pages\Http\Controllers\SpaceHomeController;
use App\Modules\Pages\Http\Controllers\TrashController;
use Illuminate\Support\Facades\Route;

Route::get('/spaces/{space}', [SpaceHomeController::class, 'show'])->name('spaces.show');
Route::get('/spaces/{space}/pages/new', [PageController::class, 'create'])->name('pages.create');
Route::post('/spaces/{space}/pages', [PageController::class, 'store'])->name('pages.store');
Route::get('/spaces/{space}/trash', [TrashController::class, 'index'])->name('trash.index');

Route::get('/pages/{page}', [PageController::class, 'show'])->whereNumber('page')->name('pages.show');
Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->whereNumber('page')->name('pages.edit');
Route::put('/pages/{page}', [PageController::class, 'update'])->whereNumber('page')->name('pages.update');
Route::get('/pages/{page}/move', [PageController::class, 'moveForm'])->whereNumber('page')->name('pages.move');
Route::put('/pages/{page}/move', [PageController::class, 'move'])->whereNumber('page')->name('pages.move.update');
Route::delete('/pages/{page}', [PageController::class, 'destroy'])->whereNumber('page')->name('pages.destroy');
Route::post('/pages/{page}/place', [PageTreeController::class, 'place'])->whereNumber('page')->name('pages.place');
Route::post('/pages/{page}/duplicate', [PageCopyController::class, 'duplicate'])->whereNumber('page')->name('pages.duplicate');
Route::get('/pages/{page}/copy', [PageCopyController::class, 'create'])->whereNumber('page')->name('pages.copy');
Route::post('/pages/{page}/copy', [PageCopyController::class, 'store'])->whereNumber('page')->name('pages.copy.store');
Route::post('/pages/{page}/favorite', [FavoriteController::class, 'toggle'])->whereNumber('page')->name('pages.favorite');

Route::get('/pages/{page}/versions', [PageVersionController::class, 'index'])->whereNumber('page')->name('pages.versions');
Route::get('/pages/{page}/versions/{version}', [PageVersionController::class, 'show'])->whereNumber(['page', 'version'])->name('pages.versions.show');
Route::post('/pages/{page}/versions/{version}/restore', [PageVersionController::class, 'restore'])->whereNumber(['page', 'version'])->name('pages.versions.restore');

Route::post('/pages/{page}/attachments', [AttachmentController::class, 'store'])->whereNumber('page')
    ->middleware('throttle:uploads')->name('attachments.store');
Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])->whereNumber('attachment')->name('attachments.show');
Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->whereNumber('attachment')->name('attachments.destroy');

Route::post('/trash/{page}/restore', [TrashController::class, 'restore'])->whereNumber('page')->withTrashed()->name('trash.restore');
Route::delete('/trash/{page}', [TrashController::class, 'destroy'])->whereNumber('page')->withTrashed()->name('trash.destroy');

Route::get('/labels/{label}', [LabelController::class, 'show'])->name('labels.show');

// Former German paths – also used by links stored in page content.
LegacyRedirects::register('seiten', 'pages', [
    'bearbeiten' => 'edit', 'verschieben' => 'move', 'versionen' => 'versions', 'anhaenge' => 'attachments',
]);
LegacyRedirects::register('anhaenge', 'attachments');
