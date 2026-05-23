<?php

use App\Http\Controllers\Auth\VenueSelectorController;
use App\Http\Controllers\Settings\KitchenStationController;
use App\Http\Controllers\Settings\PreparationStatusController;
use App\Http\Controllers\Settings\ServiceLocationController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\VenueController;
use App\Http\Controllers\Settings\VenueSettingsController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Venue selector — auth required, no tenant context yet
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->post('/account/venue/{id}', [VenueSelectorController::class, 'store'])
    ->name('venue.select');

// Operational routes — auth + tenant context required
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'tenant',
])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    Route::get('/attendances', fn () => Inertia::render('Attendances/Index'))->name('attendances.index');
    Route::get('/order-taker', fn () => Inertia::render('Orders/Index'))->name('orders.index');
    Route::get('/kitchen', fn () => Inertia::render('Kitchen/Index'))->name('kitchen.index');
    Route::get('/payment', fn () => Inertia::render('Payment/Index'))->name('payment.index');
    Route::get('/menu', fn () => Inertia::render('Menu/Index'))->name('menu.index');

    // Settings — owner or general manager only
    Route::prefix('settings')->name('settings.')->middleware(['role:owner,general_manager'])->group(function () {
        Route::get('/', fn () => Inertia::render('Settings/Index'))->name('index');

        Route::get('/venue', [VenueController::class, 'edit'])->name('venue');
        Route::put('/venue', [VenueController::class, 'update'])->name('venue.update');

        Route::get('/general', [VenueSettingsController::class, 'edit'])->name('general');
        Route::put('/general', [VenueSettingsController::class, 'update'])->name('general.update');

        Route::get('/kitchen-stations', [KitchenStationController::class, 'index'])->name('kitchen-stations.index');
        Route::post('/kitchen-stations', [KitchenStationController::class, 'store'])->name('kitchen-stations.store');
        Route::put('/kitchen-stations/{station}', [KitchenStationController::class, 'update'])->name('kitchen-stations.update');
        Route::delete('/kitchen-stations/{station}', [KitchenStationController::class, 'destroy'])->name('kitchen-stations.destroy');

        Route::get('/preparation-statuses', [PreparationStatusController::class, 'index'])->name('preparation-statuses.index');
        Route::post('/preparation-statuses', [PreparationStatusController::class, 'store'])->name('preparation-statuses.store');
        Route::put('/preparation-statuses/{status}', [PreparationStatusController::class, 'update'])->name('preparation-statuses.update');
        Route::delete('/preparation-statuses/{status}', [PreparationStatusController::class, 'destroy'])->name('preparation-statuses.destroy');

        Route::get('/service-locations', [ServiceLocationController::class, 'index'])->name('service-locations.index');
        Route::post('/service-locations', [ServiceLocationController::class, 'store'])->name('service-locations.store');
        Route::put('/service-locations/{location}', [ServiceLocationController::class, 'update'])->name('service-locations.update');
        Route::delete('/service-locations/{location}', [ServiceLocationController::class, 'destroy'])->name('service-locations.destroy');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
