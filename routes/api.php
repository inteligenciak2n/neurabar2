<?php

use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\TranslationsController;
use App\Models\Tenant\PlanCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/set/translations/{page}', [TranslationsController::class, 'setTranslations'])->name('api.set.translations');
Route::post('/set/locale', [TranslationsController::class, 'setLocale'])->name('api.set.locale');
Route::get('/available-languages', [TranslationsController::class, 'availableLanguages'])->name('api.available-languages');

Route::get('/plans/public', function () {
    $plans = PlanCatalog::where('active', true)
        ->orderBy('sort_order')
        ->get(['name', 'description', 'monthly_price', 'code']);

    $landingUrl = rtrim((string) config('platform.landing_page_url'), '/');

    return response()->json($plans)
        ->header('Access-Control-Allow-Origin', $landingUrl)
        ->header('Access-Control-Allow-Methods', 'GET')
        ->header('Vary', 'Origin')
        ->header('Cache-Control', 'public, max-age=300');
})->name('api.plans.public');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhooks/payment/{gateway}', PaymentWebhookController::class)
    ->whereIn('gateway', config('subscription.payment.supported_gateways', ['asaas']))
    ->middleware('throttle:300,1')
    ->name('api.webhooks.payment');
