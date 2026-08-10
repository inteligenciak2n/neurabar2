<?php

namespace Tests\Feature\Translations;

use App\Services\Languages\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TranslationsControllerTest extends TestCase
{
    private string $translationsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translationsPath = sys_get_temp_dir().'/neurabar-http-translations-'.bin2hex(random_bytes(8));
        $this->app->instance(TranslationService::class, new TranslationService($this->translationsPath));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->translationsPath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->translationsPath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir($this->translationsPath);
        }

        parent::tearDown();
    }

    public function test_it_returns_only_requested_components_for_the_current_locale(): void
    {
        $this->writeTranslations('pt', 'Dashboard', ['Dashboard' => 'Painel']);
        $this->writeTranslations('pt', 'Unused', ['Hidden' => 'Oculto']);

        $this->withSession(['locale' => 'pt'])
            ->getJson(route('api.translations.index', [
                'components' => ['Dashboard', 'AppLayout'],
            ]))
            ->assertOk()
            ->assertExactJson([
                'locale' => 'pt',
                'translations' => [
                    'Dashboard' => ['Dashboard' => 'Painel'],
                    'AppLayout' => [],
                ],
            ]);
    }

    public function test_translation_endpoints_use_the_web_session_middleware(): void
    {
        foreach ([
            'api.translations.index',
            'api.translations.store',
            'api.set.translations',
            'api.set.locale',
            'api.available-languages',
        ] as $routeName) {
            $this->assertContains('web', Route::getRoutes()->getByName($routeName)->gatherMiddleware());
        }
    }

    public function test_inertia_language_definitions_only_contain_locale_metadata(): void
    {
        session(['locale' => 'pt']);

        $definitions = TranslationService::getLanguagesDefinitions(Request::create('/'));

        $this->assertSame('pt', $definitions['locale']);
        $this->assertArrayHasKey('locales', $definitions);
        $this->assertArrayNotHasKey('lang', $definitions);
        $this->assertArrayNotHasKey('session', $definitions);
    }

    public function test_it_falls_back_when_the_session_locale_is_not_supported(): void
    {
        session(['locale' => '../../unsafe']);

        $definitions = TranslationService::getLanguagesDefinitions(Request::create('/'));

        $this->assertSame(TranslationService::DEFAULT_LOCALE, $definitions['locale']);
    }

    public function test_it_registers_missing_strings_in_one_request(): void
    {
        $this->postJson(route('api.translations.store'), [
            'translations' => [
                ['component' => 'Dashboard', 'strings' => ['Dashboard', 'Welcome']],
                ['component' => 'AppLayout', 'strings' => ['Settings']],
            ],
        ])->assertNoContent();

        $service = $this->app->make(TranslationService::class);

        $this->assertSame([
            'Dashboard' => 'Dashboard',
            'Welcome' => 'Welcome',
        ], $service->getTranslateData('Dashboard'));
        $this->assertSame([
            'Settings' => 'Settings',
        ], $service->getTranslateData('AppLayout'));
    }

    public function test_it_rejects_invalid_or_duplicate_components(): void
    {
        $this->getJson(route('api.translations.index', [
            'components' => ['Dashboard', 'Dashboard', '../../unsafe'],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['components.1', 'components.2']);
    }

    private function writeTranslations(string $locale, string $component, array $translations): void
    {
        $directory = $this->translationsPath.'/'.$locale;

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(
            $directory.'/'.$component.'.json',
            json_encode($translations, JSON_THROW_ON_ERROR),
        );
    }
}
