<?php

use App\Http\Controllers\Kitchen\KdsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    // ]);
})->name('welcome');

// Public customer-facing order display. The `venue` query param drives the
// query, so the URL must be signed — otherwise anyone could enumerate venue
// ids and read another tenant's open orders.
Route::get('/kitchen/monitor', [KdsController::class, 'monitor'])
    ->middleware(['signed', 'throttle:60,1'])
    ->name('kitchen.monitor');

require __DIR__.'/web/guest.php';
require __DIR__.'/web/onboarding.php';
require __DIR__.'/web/operational.php';
require __DIR__.'/web/corporation.php';
require __DIR__.'/web/platform.php';
