<?php

namespace App\Http\Controllers;

use App\Services\Languages\TranslationService;
use Illuminate\Http\Request;

class TranslationsController extends Controller
{
    public function __construct(
        protected TranslationService $translationService
    ){}

    public function setTranslations(Request $request, string $route)
    {
        return $this->translationService->setTranslations($request, $route);
    }

    public function setLocale(Request $request)
    {
        return $this->translationService->setLocale($request);
    }

    public function availableLanguages()
    {
        return $this->translationService->getAvailableLanguages();
    }
}
