<?php

use App\Http\Controllers\Guest\Delivery\DeliveryCustomerLookupController;
use App\Http\Controllers\Guest\Delivery\DeliveryFeeZoneLookupController;
use App\Http\Controllers\Guest\Delivery\DeliveryMenuController;
use App\Http\Controllers\Guest\Delivery\DeliveryOrderController;
use App\Http\Controllers\Guest\Delivery\RequestDeliveryPhoneOtpController;
use App\Http\Controllers\Guest\Delivery\VerifyDeliveryPhoneOtpController;
use Illuminate\Support\Facades\Route;

// Public delivery/pickup ordering flow — no auth required, token identifies the venue.
Route::prefix('delivery/{token}')->name('guest.delivery.')->middleware('billing.active')->group(function () {
    Route::get('/', [DeliveryMenuController::class, 'show'])->name('menu')->middleware('throttle:60,1');
    Route::get('/customer', [DeliveryCustomerLookupController::class, 'show'])->name('customer')->middleware('throttle:delivery-customer-lookup');
    Route::get('/customer/data', [DeliveryCustomerLookupController::class, 'reveal'])->name('customer.data')->middleware('throttle:20,1');
    Route::post('/phone/otp', [RequestDeliveryPhoneOtpController::class, 'store'])->name('phone.otp.request')->middleware('throttle:delivery-phone-otp');
    Route::post('/phone/otp/verify', [VerifyDeliveryPhoneOtpController::class, 'store'])->name('phone.otp.verify')->middleware('throttle:delivery-phone-otp');
    Route::get('/fee-zones/lookup', [DeliveryFeeZoneLookupController::class, 'show'])->name('fee-zones.lookup')->middleware('throttle:30,1');
    Route::post('/orders', [DeliveryOrderController::class, 'store'])->name('orders.store')->middleware('throttle:20,1');
});
