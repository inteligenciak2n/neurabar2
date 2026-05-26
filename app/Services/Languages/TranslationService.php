<?php

namespace App\Services\Languages;

use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;


class TranslationService
{
    protected string $storage_path;

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
        $locale = $this->getCustomLocale();

        $file_path = $this->storage_path . "/{$locale}/{$route}.json";

        if( !file_exists( dirname($file_path) ) ){
            try{
                mkdir( dirname($file_path), 0755, true);
            } catch (\Exception $e) {
                // Handle the exception
                Log::info("Failed mkdir: ", [$file_path, $route, $locale]);
                throw new \Exception("Failed to create directory: " . $e->getMessage());
            }
            
            
        }

        if( !file_exists($file_path) ){

            $data[$request->value] = $request->value;
            file_put_contents( $file_path, json_encode($data, JSON_PRETTY_PRINT) );

            return;
        }

        $data = file_get_contents( $file_path );
        if($data){
            $data = json_decode($data, true);
            if($data){

                $data[$request->value] = $request->value;
                file_put_contents( $file_path, json_encode($data, JSON_PRETTY_PRINT) );
            }
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

    public function getTranslateData(string $route, string $locale = 'pt')
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

    public function getAllTranslations( $locale = 'pt' )
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