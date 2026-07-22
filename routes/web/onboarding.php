<?php

use App\Http\Controllers\Onboarding\CorporationController;
use App\Http\Controllers\Onboarding\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Wizard de onboarding — auth + email verificado, sem contexto de tenant (venue) ainda
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/subscription', [SubscriptionController::class, 'create'])->name('subscription.create');
        Route::post('/subscription', [SubscriptionController::class, 'store'])->name('subscription.store');

        Route::get('/corporation', [CorporationController::class, 'create'])->name('corporation.create');
        Route::post('/corporation', [CorporationController::class, 'store'])->name('corporation.store');
    });
