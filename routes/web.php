<?php

use App\Http\Controllers\Auth\VenueSelectorController;
use App\Http\Controllers\Guest\PublicMenuController;
use App\Http\Controllers\Menu\CategoryController;
use App\Http\Controllers\Menu\ProductController;
use App\Http\Controllers\Orders\AttendanceController;
use App\Http\Controllers\Orders\OrderController;
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

// Public guest routes — no auth required
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/menu/{slug}', [PublicMenuController::class, 'show'])->name('menu.public');
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

    // Menu — edit routes restricted to managers; products page accessible by all
    Route::prefix('menu')->name('menu.')->middleware(['role:corporation_admin,owner,general_manager'])->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/toggle', [ProductController::class, 'toggleActive'])->name('products.toggle');
    });

    // Attendances
    Route::prefix('attendances')->name('attendances.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::get('/{attendance}', [AttendanceController::class, 'show'])->name('show');
        Route::put('/{attendance}', [AttendanceController::class, 'update'])->name('update');
        Route::post('/{attendance}/close', [AttendanceController::class, 'close'])->name('close');
        Route::post('/{attendance}/orders', [OrderController::class, 'store'])->name('orders.store');
    });

    // Order Taker
    Route::get('/orders/take/{attendance}', [OrderController::class, 'create'])->name('orders.take');

    Route::get('/kitchen', fn () => Inertia::render('Kitchen/Index'))->name('kitchen.index');
    Route::get('/payment', fn () => Inertia::render('Payment/Index'))->name('payment.index');

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
