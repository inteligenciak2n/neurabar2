<?php

use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\TranslationsController;
use App\Models\Tenant\PlanCatalog;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::get('/translations', [TranslationsController::class, 'index'])
        ->middleware('throttle:120,1')
        ->name('api.translations.index');

    Route::post('/translations', [TranslationsController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('api.translations.store');

    Route::post('/set/translations/{page}', [TranslationsController::class, 'setTranslations'])
        ->where('page', '[A-Za-z0-9][A-Za-z0-9._-]{0,119}')
        ->middleware('throttle:60,1')
        ->name('api.set.translations');

    Route::post('/set/locale', [TranslationsController::class, 'setLocale'])->name('api.set.locale');
    Route::get('/available-languages', [TranslationsController::class, 'availableLanguages'])->name('api.available-languages');
});

Route::get('/plans/public', function () {
    $plans = PlanCatalog::where('active', true)
        ->orderBy('sort_order')
        ->get(['name', 'description', 'monthly_price', 'code'])
        // A landing page é um consumidor externo: mantemos o contrato em reais
        // decimais mesmo depois da migração interna para centavos.
        ->map(fn (PlanCatalog $plan): array => [
            'code' => $plan->code,
            'name' => $plan->name,
            'description' => $plan->description,
            'monthly_price' => Money::toFloat((int) $plan->monthly_price),
        ]);

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
