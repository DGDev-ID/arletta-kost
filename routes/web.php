<?php

use App\Http\Controllers\Management\BillController;
use App\Http\Controllers\Management\TenantController;
use App\Http\Controllers\Master\KostController;
use App\Http\Controllers\Master\RoomCategoryController;
use App\Http\Controllers\Master\RoomController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('master')->name('master.')->group(function () {
    Route::resource('kosts', KostController::class)->except(['show']);
    Route::resource('rooms', RoomController::class)->except(['show']);

    Route::get('room-categories', [RoomCategoryController::class, 'index'])->name('room-categories.index');
    Route::get('room-categories/kosts', [RoomCategoryController::class, 'kosts'])->name('room-categories.kosts');
    Route::post('room-categories', [RoomCategoryController::class, 'store'])->name('room-categories.store');
    Route::put('room-categories/{roomCategory}', [RoomCategoryController::class, 'update'])->name('room-categories.update');
    Route::delete('room-categories/{roomCategory}', [RoomCategoryController::class, 'destroy'])->name('room-categories.destroy');
});

Route::middleware(['auth', 'verified'])->prefix('management')->name('management.')->group(function () {
    Route::resource('tenants', TenantController::class);
    Route::post('bills', [BillController::class, 'store'])->name('bills.store');
    Route::patch('bills/{bill}/status', [BillController::class, 'updateStatus'])->name('bills.update-status');
});

Route::middleware(['auth', 'verified'])->prefix('transactions')->name('transactions.')->group(function () {
    Route::get('/', [TransactionController::class, 'index'])->name('index');
    Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
    Route::post('/{transaction}/refund', [TransactionController::class, 'refund'])->name('refund');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
