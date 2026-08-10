<?php

namespace App\Services\Languages;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;

class TranslationService
{
    private const COMPONENT_PATTERN = '/\A[A-Za-z0-9][A-Za-z0-9._-]{0,119}\z/';

    protected string $storage_path;

    const DEFAULT_LOCALE = 'en';

    public function __construct(?string $storagePath = null)
    {
        $this->storage_path = $storagePath ?? resource_path('translations');
        // Constructor can be used for dependency injection if needed
    }

    /** @return array{locale: string, locales: list<array{value: string, label: string}>} */
    public static function getLanguagesDefinitions(Request $request): array
    {
        $service = new self;
        $locale = $service->getCustomLocale();

        return [
            'locale' => $locale,
            'locales' => self::getAvailableLanguages(),
        ];
    }

    public static function getAvailableLanguages()
    {
        return [
            ['value' => 'en', 'label' => 'English'],
            ['value' => 'es', 'label' => 'Español'],
            ['value' => 'pt', 'label' => 'Português'],
        ];
    }

    public static function getLanguageKeys(): array
    {
        $languages = self::getAvailableLanguages();

        return array_column($languages, 'value');
    }

    public function setTranslations(Request $request, string $route): void
    {
        $this->setTranslationsBatch([
            $route => [(string) $request->input('value')],
        ]);
    }

    /**
     * @param  array<string, list<string>>  $translationsByComponent
     */
    public function setTranslationsBatch(array $translationsByComponent): void
    {
        foreach ($translationsByComponent as $component => $translations) {
            $this->assertValidComponent($component);
            $this->writeMissingTranslations($component, $translations);
        }
    }

    /**
     * @param  list<string>  $components
     * @return array<string, array<string, string>>
     */
    public function getTranslationsForComponents(array $components, string $locale = self::DEFAULT_LOCALE): array
    {
        $translations = [];

        foreach (array_unique($components) as $component) {
            $this->assertValidComponent($component);
            $translations[$component] = $this->getTranslateData($component, $locale);
        }

        return $translations;
    }

    /**
     * @param  list<string>  $translations
     */
    private function writeMissingTranslations(string $component, array $translations): void
    {
        $locale = self::DEFAULT_LOCALE;
        $file_path = $this->storage_path."/{$locale}/{$component}.json";
        $dir = dirname($file_path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fp = fopen($file_path, 'c+');
        if ($fp === false) {
            throw new \Exception("Não foi possível abrir/criar o arquivo: {$file_path}");
        }

        try {
            if (! flock($fp, LOCK_EX)) {
                throw new \Exception("Falha ao obter trava exclusiva para: {$file_path}");
            }

            $current_data = stream_get_contents($fp);
            $data = $current_data ? json_decode($current_data, true) : [];

            if ($current_data && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON inválido no arquivo: '.json_last_error_msg());
            }

            foreach (array_unique($translations) as $translation) {
                if ($translation !== '' && ! array_key_exists($translation, $data)) {
                    $data[$translation] = $translation;
                }
            }

            ftruncate($fp, 0);
            rewind($fp);

            $new_content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if (fwrite($fp, $new_content) === false) {
                throw new \Exception('Falha ao escrever no arquivo');
            }
            fflush($fp);

            flock($fp, LOCK_UN);
        } finally {
            fclose($fp);
        }
    }

    private function assertValidComponent(string $component): void
    {
        if (preg_match(self::COMPONENT_PATTERN, $component) !== 1) {
            throw new InvalidArgumentException('Nome de arquivo de tradução inválido.');
        }
    }

    public static function getTranslations(string $page, string $locale)
    {
        $service = new self;
        $file_path = $service->storage_path."/{$locale}/{$page}.json";

        if (! file_exists($file_path)) {
            return [];
        }

        $data = file_get_contents($file_path);
        if ($data) {
            $data = json_decode($data, true);
            if ($data) {
                return $data;
            }
        }

        return [];
    }

    public function getTranslateData(string $route, string $locale = self::DEFAULT_LOCALE)
    {
        $file_path = $this->storage_path."/{$locale}/{$route}.json";

        if (! file_exists($file_path)) {
            return [];
        }

        $data = file_get_contents($file_path);
        if ($data) {
            return json_decode($data, true);
        }

        return [];
    }

    public function getAllTranslations($locale = self::DEFAULT_LOCALE)
    {
        $files = glob($this->storage_path."/{$locale}/*.json");
        $all_data = [];
        foreach ($files as $file_path) {
            $data = file_get_contents($file_path);
            if ($data) {
                $data = json_decode($data, true);
                if ($data) {
                    $file_name = pathinfo($file_path, PATHINFO_FILENAME);
                    $all_data[$file_name] = $data;
                }
            }
        }

        return $all_data;
    }

    public function getCustomLocale(): string
    {
        $locale = Session::has('locale')
            ? Session::get('locale')
            : ($_COOKIE['user_locale'] ?? App::currentLocale());

        return in_array($locale, self::getLanguageKeys(), true)
            ? $locale
            : self::DEFAULT_LOCALE;
    }

    public function setLocale(Request $request)
    {
        $locale = $request->has('locale') ? $request->input('locale') : $request->getLocale();
        $this->setCustomLocale($locale);
        $cookie = cookie('user_locale', $locale);

        return response()->json(['message' => 'Locale set successfully'])->cookie($cookie);
    }

    public function setCustomLocale(string $locale)
    {
        if (! in_array($locale, self::getLanguageKeys())) {
            $locale = 'pt';
        }
        Session::put('locale', $locale);
        App::setLocale($locale);
    }
}
