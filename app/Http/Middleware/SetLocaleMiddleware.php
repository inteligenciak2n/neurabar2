<?php

namespace App\Http\Middleware;

use App\Services\Languages\TranslationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = TranslationService::getLanguageKeys();
        $cookieLocale = $request->cookie('user_locale');
        $locale = is_string($cookieLocale) && in_array($cookieLocale, $availableLocales, true)
            ? $cookieLocale
            : TranslationService::DEFAULT_LOCALE;

        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }
}
