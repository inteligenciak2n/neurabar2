<?php

use App\Events\SocketTest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

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
