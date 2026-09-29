<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\ReadingListController;
use App\Http\Controllers\ShareLinkController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::redirect('dashboard', '/lists')->name('dashboard');

    Route::get('lists', [ReadingListController::class, 'index'])->name('lists.index');
    Route::get('lists/new', [ReadingListController::class, 'create'])->name('lists.create');
    Route::post('lists', [ReadingListController::class, 'store'])->name('lists.store');
});

// A single list answers 404 to everyone but its owner, guests included (ReadingListPolicy), so a deleted or
// someone else's list looks the same as one that never existed.
Route::whereNumber('list')->group(function () {
    Route::get('lists/{list}', [ReadingListController::class, 'show'])->name('lists.show');
    Route::get('lists/{list}/edit', [ReadingListController::class, 'edit'])->name('lists.edit');
    Route::patch('lists/{list}', [ReadingListController::class, 'update'])->name('lists.update');
    Route::delete('lists/{list}', [ReadingListController::class, 'destroy'])->name('lists.destroy');

    Route::get('lists/{list}/books/search', [BookController::class, 'search'])->name('lists.books.search');
    Route::post('lists/{list}/books', [BookController::class, 'store'])->name('lists.books.store');

    Route::post('lists/{list}/share-links', [ShareLinkController::class, 'store'])->name('lists.share-links.store');
});

// Read-only share links (anyone, no sign-in): see App\Services\ShareLinks.
Route::get('s/{token}', [ShareLinkController::class, 'show'])->name('share.show');

require __DIR__.'/settings.php';
