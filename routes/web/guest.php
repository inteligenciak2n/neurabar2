<?php

use App\Http\Controllers\Guest\GuestCheckoutController;
use App\Http\Controllers\Guest\GuestHubController;
use App\Http\Controllers\Guest\GuestOrderController;
use App\Http\Controllers\Guest\GuestSessionController;
use App\Http\Controllers\Guest\PublicMenuController;
use App\Http\Controllers\Guest\TrackOrderController;
use Illuminate\Support\Facades\Route;

// Public guest routes — no auth required
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/order/{order}/track', [TrackOrderController::class, 'show'])->name('orders.track');
});

// Guest Hub — QR-code-based visitor flow
Route::prefix('g/{token}')->name('guest.')->middleware('billing.active')->group(function () {
    Route::get('/', [GuestHubController::class, 'show'])->name('hub')->middleware('throttle:60,1');
    Route::get('/menu', [PublicMenuController::class, 'show'])->name('menu')->middleware('throttle:60,1');
    Route::post('/session', [GuestSessionController::class, 'store'])->name('session.store')->middleware('throttle:10,1');
    Route::post('/session/verify', [GuestSessionController::class, 'verify'])->name('session.verify')->middleware('throttle:3,15');
    Route::get('/orders', [GuestOrderController::class, 'index'])->name('orders.index')->middleware('throttle:30,1');
    Route::post('/orders', [GuestOrderController::class, 'store'])->name('orders.store')->middleware('throttle:30,1');
    Route::post('/signal', [GuestHubController::class, 'signal'])->name('signal')->middleware('throttle:10,1');
    Route::post('/request-order', [GuestHubController::class, 'requestOrderAssistance'])->name('request-order')->middleware('throttle:10,1');
    Route::post('/checkout', [GuestCheckoutController::class, 'store'])->name('checkout')->middleware('throttle:5,1');
    Route::post('/verify-location', [GuestHubController::class, 'verifyLocation'])->name('verify-location')->middleware('throttle:10,1');
});
