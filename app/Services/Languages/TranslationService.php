<?php

namespace App\Services\Languages;

use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;


class TranslationService
{
    protected string $storage_path;
    const DEFAULT_LOCALE = 'en';

    public function __construct()
    {
        $this->storage_path = resource_path('translations');
        // Constructor can be used for dependency injection if needed
    }

    static public function getLanguagesDefinitions(Request $request)
    {
        $service = new Self();
        $page = $request->route()?->getName() ?? 'common';
        $locale = $service->getCustomLocale();

        // dd($locale);
        return [
            'lang' => $service->getAllTranslations($locale),
            // 'lang' => $service->getTranslateData($page, $locale),
            'session' => session(),
            'locale' => $locale,
            'locales' => self::getAvailableLanguages(),
            ];
    }

    static public function getAvailableLanguages()
    {
        return [
            ['value' =>'en', 'label' => 'English'],
            ['value' =>'es', 'label' => 'Español'],
            ['value' =>'pt', 'label' => 'Português'],
        ];
    }

    static public function getLanguageKeys() : array
    {
        $languages = self::getAvailableLanguages();
        return array_column($languages, 'value');
    }

    public function setTranslations(Request $request, string $route)
    {
        $locale = SELF::DEFAULT_LOCALE;
        $file_path = $this->storage_path . "/{$locale}/{$route}.json";
        $dir = dirname($file_path);

        // Cria diretório se não existir (ignora se já existe)
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fp = fopen($file_path, 'c+');
        if ($fp === false) {
            throw new \Exception("Não foi possível abrir/criar o arquivo: {$file_path}");
        }

        try {
            if (!flock($fp, LOCK_EX)) {
                throw new \Exception("Falha ao obter trava exclusiva para: {$file_path}");
            }

            // Lê o conteúdo atual
            $current_data = stream_get_contents($fp);
            $data = $current_data ? json_decode($current_data, true) : [];

            if ($current_data && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON inválido no arquivo: " . json_last_error_msg());
            }

            $key = $request->value;
            $value = $request->value;
            $data[$key] = $value;

            // Prepara para escrever de volta
            ftruncate($fp, 0);
            rewind($fp);

            $new_content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if (fwrite($fp, $new_content) === false) {
                throw new \Exception("Falha ao escrever no arquivo");
            }
            fflush($fp);

            flock($fp, LOCK_UN);
        } finally {
            fclose($fp); // Garantia de fechamento mesmo com exceção
        }
    }

    public static function getTranslations(string $page, string $locale)
    {
        $service = new Self();
        $file_path = $service->storage_path . "/{$locale}/{$page}.json";

        if (!file_exists($file_path)) {
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
        $file_path = $this->storage_path . "/{$locale}/{$route}.json";

        if( !file_exists($file_path) ){
            return [];
        }

        $data = file_get_contents( $file_path );
        if($data){
            return json_decode($data, true);
        }
        return [];
    }

    public function getAllTranslations( $locale = self::DEFAULT_LOCALE )
    {
        $files = glob( $this->storage_path . "/{$locale}/*.json" );
        $all_data = [];
        foreach( $files as $file_path ){
            $data = file_get_contents( $file_path );
            if($data){
                $data = json_decode($data, true);
                if($data){
                    $file_name = pathinfo($file_path, PATHINFO_FILENAME);
                    $all_data[$file_name] = $data;
                }
            }
        }
        return $all_data;
    }

    public function getCustomLocale()
    {
        if(Session::has('locale')){
            return Session::get('locale');
        }
        return isset($_COOKIE['user_locale']) ? $_COOKIE['user_locale'] : App::currentLocale();
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
        if( !in_array($locale, self::getLanguageKeys()) ){
            $locale = 'pt';
        }
        Session::put('locale', $locale);
        App::setLocale($locale);
    }
}