<?php

namespace Tests\Unit\Services\Languages;

use App\Services\Languages\TranslationService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TranslationServiceTest extends TestCase
{
    private string $translationsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translationsPath = sys_get_temp_dir().'/neurabar-translations-'.bin2hex(random_bytes(8));
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

    public function test_it_creates_and_updates_a_translation_file(): void
    {
        $service = new TranslationService($this->translationsPath);

        $service->setTranslations(new Request(['value' => 'Hello']), 'Dashboard');
        $service->setTranslations(new Request(['value' => 'Welcome']), 'Dashboard');

        $translations = json_decode(
            file_get_contents($this->translationsPath.'/en/Dashboard.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame([
            'Hello' => 'Hello',
            'Welcome' => 'Welcome',
        ], $translations);
    }

    public function test_it_reads_only_requested_translation_namespaces(): void
    {
        $this->writeTranslations('pt', 'Dashboard', ['Dashboard' => 'Painel']);
        $this->writeTranslations('pt', 'AppLayout', ['Settings' => 'Configurações']);
        $this->writeTranslations('pt', 'Unused', ['Hidden' => 'Oculto']);

        $service = new TranslationService($this->translationsPath);

        $this->assertSame([
            'Dashboard' => ['Dashboard' => 'Painel'],
            'Missing' => [],
            'AppLayout' => ['Settings' => 'Configurações'],
        ], $service->getTranslationsForComponents(['Dashboard', 'Missing', 'Dashboard', 'AppLayout'], 'pt'));
    }

    public function test_it_registers_missing_translations_in_batches_without_overwriting_existing_values(): void
    {
        $this->writeTranslations('en', 'Dashboard', ['Dashboard' => 'Custom dashboard']);

        $service = new TranslationService($this->translationsPath);
        $service->setTranslationsBatch([
            'Dashboard' => ['Dashboard', 'Welcome', 'Welcome'],
            'AppLayout' => ['Settings'],
        ]);

        $this->assertSame([
            'Dashboard' => 'Custom dashboard',
            'Welcome' => 'Welcome',
        ], $service->getTranslateData('Dashboard'));
        $this->assertSame([
            'Settings' => 'Settings',
        ], $service->getTranslateData('AppLayout'));
    }

    public function test_it_rejects_a_translation_path_outside_the_locale_directory(): void
    {
        $service = new TranslationService($this->translationsPath);

        $this->expectException(InvalidArgumentException::class);

        $service->setTranslations(new Request(['value' => 'Unsafe']), '../../outside');
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
