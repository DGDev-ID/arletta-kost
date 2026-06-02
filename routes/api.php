<?php

use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\InquiryApiController;
use App\Http\Controllers\Api\KostApiController;
use App\Http\Controllers\Api\PromoApiController;
use App\Http\Controllers\Api\RefundApiController;
use App\Http\Controllers\Api\RoomApiController;
use App\Http\Controllers\Api\RoomCategoryApiController;
use App\Http\Controllers\Api\SearchApiController;
use App\Http\Controllers\Api\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Arletta Kost Landing Page
|--------------------------------------------------------------------------
|
| Public endpoints consumed by the landing page frontend.
| All routes are prefixed with /api automatically.
|
*/

// ─── Global / Landing ───────────────────────────────────────────────
Route::get('stats', [SearchApiController::class, 'stats']);
Route::get('search', [SearchApiController::class, 'search']);
Route::get('price-range', [SearchApiController::class, 'priceRange']);
Route::get('featured-kosts', [SearchApiController::class, 'featuredKosts']);
Route::get('featured-categories', [SearchApiController::class, 'featuredCategories']);

// ─── Kosts ──────────────────────────────────────────────────────────
Route::get('kosts', [KostApiController::class, 'index']);
Route::get('kosts/{kost}', [KostApiController::class, 'show']);
Route::get('kosts/{kost}/stats', [KostApiController::class, 'stats']);
Route::get('kosts/{kost}/contact-info', [KostApiController::class, 'contactInfo']);

// ─── Kost → Categories ─────────────────────────────────────────────
Route::get('kosts/{kost}/categories', [RoomCategoryApiController::class, 'index']);

// ─── Categories (standalone) ────────────────────────────────────────
Route::get('categories/{roomCategory}', [RoomCategoryApiController::class, 'show']);
Route::get('categories/{roomCategory}/images', [RoomCategoryApiController::class, 'images']);

// ─── Kost → Rooms ───────────────────────────────────────────────────
Route::get('kosts/{kost}/rooms', [RoomApiController::class, 'index']);

// ─── Rooms (standalone) ─────────────────────────────────────────────
Route::get('rooms', [RoomApiController::class, 'list']);
Route::get('rooms/{room}', [RoomApiController::class, 'show']);

// ─── Inquiry (contact form) ─────────────────────────────────────────
Route::post('inquiries', [InquiryApiController::class, 'store']);

// ─── Bookings / Transactions ─────────────────────────────────────────
Route::post('bookings', [BookingApiController::class, 'store']);

// ─── Refund Request ─────────────────────────────────────────────────
Route::post('refund-request/check-transaction', [RefundApiController::class, 'checkTransaction']);
Route::post('refund-request', [RefundApiController::class, 'submitRefund']);

// ─── Promo / Voucher ────────────────────────────────────────────────
Route::post('promos/validate', [PromoApiController::class, 'validate']);

Route::post('payment-webhook', PaymentWebhookController::class);
