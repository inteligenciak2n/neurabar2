<?php

use App\Http\Controllers\Corporation\CorporationDashboardController;
use App\Http\Controllers\Corporation\VenueController as CorporationVenueController;
use Illuminate\Support\Facades\Route;

// Corporation panel — auth + tenant + owner role
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'tenant',
    'module:menu',
    'role:owner,general_manager',
])->prefix('corporation')->name('corporation.')->group(function () {
    Route::get('/dashboard', [CorporationDashboardController::class, 'index'])->name('dashboard');
    Route::post('/venues/{id}/switch', [CorporationDashboardController::class, 'switchVenue'])->name('venues.switch');
    Route::get('/venues', [CorporationVenueController::class, 'index'])->name('venues.index');
    Route::get('/venues/create', [CorporationVenueController::class, 'create'])->name('venues.create');
    Route::post('/venues', [CorporationVenueController::class, 'store'])->name('venues.store');
    Route::get('/venues/{venue}/edit', [CorporationVenueController::class, 'edit'])->name('venues.edit');
    Route::put('/venues/{venue}', [CorporationVenueController::class, 'update'])->name('venues.update');
});
