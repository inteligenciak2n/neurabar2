<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequirePlatformProfile;
use App\Http\Middleware\RequirePlatformRole;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\SetLocaleMiddleware;
use App\Http\Middleware\SetVenueContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['guest_token']);
        $middleware->web(append: [
            SetLocaleMiddleware::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->statefulApi();
        $middleware->redirectGuestsTo(fn () => route('login'));

        $middleware->alias([
            'tenant' => SetVenueContext::class,
            'role' => RequireRole::class,
            'platform_profile' => RequirePlatformProfile::class,
            'platform_role' => RequirePlatformRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (Exception $e) {
            if ($e->getPrevious() instanceof TokenMismatchException) {
                return redirect()->route('login');
            }
        });
    })->create();
