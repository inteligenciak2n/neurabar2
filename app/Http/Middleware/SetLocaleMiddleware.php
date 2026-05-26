<?php

namespace App\Http\Middleware;

use App\Services\Languages\TranslationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = TranslationService::getLanguageKeys(); // Defina os idiomas disponíveis

            // Verifica se o idioma já está na sessão
        if (Session::has('locale')) {
            App::setLocale(Session::get('locale'));
        } else {
            // Obtém o idioma preferido do navegador
            $browserLanguage = $request->getPreferredLanguage($availableLocales) ?? 'pt';
            App::setLocale($browserLanguage);
            Session::put('locale', $browserLanguage);
        }

        return $next($request);
    }
}
