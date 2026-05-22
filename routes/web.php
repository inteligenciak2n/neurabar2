<?php

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

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    // Operational sections
    Route::get('/attendances', fn () => Inertia::render('Attendances/Index'))->name('attendances.index');
    Route::get('/order-taker', fn () => Inertia::render('Orders/Index'))->name('orders.index');
    Route::get('/kitchen', fn () => Inertia::render('Kitchen/Index'))->name('kitchen.index');
    Route::get('/payment', fn () => Inertia::render('Payment/Index'))->name('payment.index');
    Route::get('/menu', fn () => Inertia::render('Menu/Index'))->name('menu.index');
    Route::get('/settings', fn () => Inertia::render('Settings/Index'))->name('settings.index');

    // Platform backoffice
    Route::prefix('platform')->name('platform.')->group(function () {
        Route::get('/dashboard', fn () => Inertia::render('Platform/Dashboard'))->name('dashboard');
        Route::get('/corporations', fn () => Inertia::render('Platform/Corporations/Index'))->name('corporations.index');
        Route::get('/plans', fn () => Inertia::render('Platform/Plans/Index'))->name('plans.index');
        Route::get('/users', fn () => Inertia::render('Platform/Users/Index'))->name('users.index');
    });
});
