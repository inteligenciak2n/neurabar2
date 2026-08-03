<?php

use App\Http\Middleware\EnsureBillingActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireModule;
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
        // Confiar em qualquer proxy permite forjar `X-Forwarded-For` e, com isso,
        // o `remoteIp` enviado ao gateway na antifraude. Por padrão confiamos
        // apenas nas faixas privadas onde o load balancer/reverse proxy roda.
        $middleware->trustProxies(at: array_filter(array_map(
            trim(...),
            explode(',', (string) env('TRUSTED_PROXIES', '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,127.0.0.1'))
        )));
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
            'module' => RequireModule::class,
            'billing.active' => EnsureBillingActive::class,
            'platform_profile' => RequirePlatformProfile::class,
            'platform_role' => RequirePlatformRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sem isso o Laravel devolve os dados do cartão para a sessão (old input)
        // em qualquer falha de validação ou exceção.
        $exceptions->dontFlash([
            'number',
            'cvv',
            'ccv',
            'card_number',
            'holder_document',
            'gateway_token',
        ]);

        $exceptions->renderable(function (Exception $e) {
            if ($e->getPrevious() instanceof TokenMismatchException) {
                return redirect()->route('login');
            }
        });
    })->create();
