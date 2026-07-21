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

Route::get('/kitchen/monitor', [KdsController::class, 'monitor'])->name('kitchen.monitor');

require __DIR__.'/web/guest.php';
require __DIR__.'/web/operational.php';
require __DIR__.'/web/corporation.php';
require __DIR__.'/web/platform.php';
