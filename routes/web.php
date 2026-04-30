<?php

use App\Http\Controllers\Management\BillController;
use App\Http\Controllers\Management\TenantController;
use App\Http\Controllers\Management\UserController;
use App\Http\Controllers\Master\KostController;
use App\Http\Controllers\Master\RoomCategoryController;
use App\Http\Controllers\Master\RoomController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Transaction\RefundRequestController;
use App\Http\Controllers\Transaction\BillApprovalController;
use App\Http\Controllers\Transaction\SignatureController;
use App\Http\Controllers\Transaction\TermsConditionController;
use App\Http\Controllers\Management\CheckoutController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('master')->name('master.')->group(function () {
    Route::resource('kosts', KostController::class)->except(['show']);
    Route::resource('rooms', RoomController::class)->except(['show']);
    Route::get('rooms/{room}/bills', [RoomController::class, 'bills'])->name('rooms.bills');
    Route::get('rooms/{room}/bills/log', [RoomController::class, 'billsLog'])->name('rooms.bills.log');

    Route::get('room-categories', [RoomCategoryController::class, 'index'])->name('room-categories.index');
    Route::get('room-categories/kosts', [RoomCategoryController::class, 'kosts'])->name('room-categories.kosts');
    Route::get('room-categories/create', [RoomCategoryController::class, 'create'])->name('room-categories.create');
    Route::post('room-categories', [RoomCategoryController::class, 'store'])->name('room-categories.store');
    Route::get('room-categories/{roomCategory}/edit', [RoomCategoryController::class, 'edit'])->name('room-categories.edit');
    Route::post('room-categories/{roomCategory}', [RoomCategoryController::class, 'update'])->name('room-categories.update');
    Route::put('room-categories/{roomCategory}', [RoomCategoryController::class, 'update'])->name('room-categories.update-json');
    Route::delete('room-categories/{roomCategory}', [RoomCategoryController::class, 'destroy'])->name('room-categories.destroy');
});

Route::middleware(['auth', 'verified'])->prefix('management')->name('management.')->group(function () {
    Route::resource('tenants', TenantController::class);
    Route::resource('users', UserController::class)->except(['show']);
    Route::post('bills', [BillController::class, 'store'])->name('bills.store');
    Route::patch('bills/{bill}/status', [BillController::class, 'updateStatus'])->name('bills.update-status');
    Route::patch('bills/transactions/{transaction}/make-success', [BillController::class, 'makeSuccess'])->name('bills.make-success');
    Route::patch('bills/transactions/{transaction}/make-failed', [BillController::class, 'makeFailed'])->name('bills.make-failed');
    
    Route::get('checkouts', [CheckoutController::class, 'index'])->name('checkouts.index');
    Route::post('checkouts/{tenant}/room/{room}', [CheckoutController::class, 'process'])->name('checkouts.process');
});

Route::middleware(['auth', 'verified'])->prefix('transactions')->name('transactions.')->group(function () {
    // Bill approval management
    Route::get('bill-approval', [BillApprovalController::class, 'index'])->name('bill-approval.index');
    Route::get('bill-approval/{bill}', [BillApprovalController::class, 'show'])->name('bill-approval.show');

    // Refund request management
    Route::get('refund-requests', [RefundRequestController::class, 'index'])->name('refund-requests.index');
    Route::patch('refund-requests/{bill}/approve', [RefundRequestController::class, 'approve'])->name('refund-requests.approve');
    Route::patch('refund-requests/{bill}/reject', [RefundRequestController::class, 'reject'])->name('refund-requests.reject');

    // Signature management
    Route::get('signatures', [SignatureController::class, 'index'])->name('signatures.index');
    Route::patch('signatures/{bill}/sign', [SignatureController::class, 'sign'])->name('signatures.sign');

    // Terms & Conditions management
    Route::post('terms-conditions', [TermsConditionController::class, 'store'])->name('terms-conditions.store');
    Route::put('terms-conditions/bulk', [TermsConditionController::class, 'bulkUpdate'])->name('terms-conditions.bulk-update');
    Route::delete('terms-conditions/{term}', [TermsConditionController::class, 'destroy'])->name('terms-conditions.destroy');

    Route::get('/', [TransactionController::class, 'index'])->name('index');
    Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
    Route::post('/{transaction}/refund', [TransactionController::class, 'refund'])->name('refund');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
