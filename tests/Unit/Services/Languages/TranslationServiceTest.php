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

    public function test_it_rejects_a_translation_path_outside_the_locale_directory(): void
    {
        $service = new TranslationService($this->translationsPath);

        $this->expectException(InvalidArgumentException::class);

        $service->setTranslations(new Request(['value' => 'Unsafe']), '../../outside');
    }
}
