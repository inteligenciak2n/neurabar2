<?php

use App\Events\SocketTest;
use App\Models\Tenant\PlanCatalog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TranslationsController;

Route::post('/set/translations/{page}', [TranslationsController::class, 'setTranslations'])->name('api.set.translations');
Route::post('/set/locale', [TranslationsController::class, 'setLocale'])->name('api.set.locale');
Route::get('/available-languages', [TranslationsController::class, 'availableLanguages'])->name('api.available-languages');

Route::get('/plans/public', function () {
    $plans = PlanCatalog::where('active', true)
        ->orderBy('sort_order')
        ->get(['name', 'description', 'monthly_price', 'code']);

    return response()->json($plans)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Cache-Control', 'public, max-age=300');
})->name('api.plans.public');


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/test-event/{user}', function (User $user) {
    try {
        $e = event(new SocketTest($user));
        Log::debug('Evento enviado!', ['user' => $user, 'event' => $e]);
    } catch (Exception $ex) {
        Log::error('Erro ao enviar evento!', ['error' => $ex->getMessage()]);
    }

    return 'Evento enviado!';
})->name('test-event');
