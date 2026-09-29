<?php

use App\Http\Controllers\ReadingListController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::redirect('dashboard', '/lists')->name('dashboard');

    Route::get('lists', [ReadingListController::class, 'index'])->name('lists.index');
    Route::get('lists/new', [ReadingListController::class, 'create'])->name('lists.create');
    Route::post('lists', [ReadingListController::class, 'store'])->name('lists.store');
    Route::get('lists/{list}', [ReadingListController::class, 'show'])->whereNumber('list')->name('lists.show');
    Route::get('lists/{list}/edit', [ReadingListController::class, 'edit'])->whereNumber('list')->name('lists.edit');
    Route::patch('lists/{list}', [ReadingListController::class, 'update'])->whereNumber('list')->name('lists.update');
    Route::delete('lists/{list}', [ReadingListController::class, 'destroy'])->whereNumber('list')->name('lists.destroy');
});

require __DIR__.'/settings.php';
