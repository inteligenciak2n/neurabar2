<?php

namespace App\Http\Controllers;

use App\Http\Requests\Translations\GetTranslationsRequest;
use App\Http\Requests\Translations\StoreTranslationsRequest;
use App\Services\Languages\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TranslationsController extends Controller
{
    public function __construct(
        protected TranslationService $translationService
    ) {}

    public function index(GetTranslationsRequest $request): JsonResponse
    {
        $locale = $this->translationService->getCustomLocale();

        return response()->json([
            'locale' => $locale,
            'translations' => $this->translationService->getTranslationsForComponents(
                $request->validated('components'),
                $locale,
            ),
        ]);
    }

    public function store(StoreTranslationsRequest $request): Response
    {
        $translationsByComponent = [];

        foreach ($request->validated('translations') as $translation) {
            $translationsByComponent[$translation['component']] = $translation['strings'];
        }

        $this->translationService->setTranslationsBatch($translationsByComponent);

        return response()->noContent();
    }

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
