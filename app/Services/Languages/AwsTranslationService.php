<?php

namespace App\Services\Languages;

use Aws\Translate\TranslateClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class AwsTranslationService
{
    private TranslateClient $translateClient;
    private bool $isConfigured;
    
    // Limite do AWS Translate para texto por requisição (5000 bytes por padrão)
    private const MAX_TEXT_SIZE = 4500; // Deixando margem de segurança
    
    // Mapeamento de idiomas
    private const LANGUAGE_MAP = [
        'pt' => 'pt',
        'en' => 'en',
        'es' => 'es'
    ];

    public function __construct()
    {
        $this->isConfigured = $this->checkConfiguration();
        
        // Só inicializa o cliente se as credenciais estiverem configuradas
        if ($this->isConfigured) {
            $this->translateClient = new TranslateClient([
                'version' => 'latest',
                'region' => config('services.aws.translate_region', config('services.aws.region', 'us-east-1')),
                'credentials' => [
                    'key' => config('services.aws.key'),
                    'secret' => config('services.aws.secret'),
                ],
            ]);
        }
    }

    /**
     * Traduz um conjunto de textos de português para os idiomas especificados
     * 
     * @param array $texts Textos em português para traduzir
     * @param array $targetLanguages Idiomas de destino ['en', 'es']
     * @return array Traduções organizadas por idioma
     */
    public function translateTexts(array $texts, array $targetLanguages = ['en', 'es']): array
    {
        // Se não estiver configurado, retorna os textos originais
        if (!$this->isConfigured) {
            Log::warning('AWS Translate não configurado. Retornando textos originais.');
            $translations = [];
            foreach ($targetLanguages as $lang) {
                $translations[$lang] = $texts;
            }
            return $translations;
        }
        
        $translations = [];
        
        // Inicializa arrays de resultado para cada idioma
        foreach ($targetLanguages as $lang) {
            $translations[$lang] = [];
        }
        
        // Agrupa textos para otimizar requisições
        $textGroups = $this->groupTexts($texts);
        
        foreach ($targetLanguages as $targetLang) {
            Log::info("Iniciando tradução para {$targetLang}");
            
            foreach ($textGroups as $groupIndex => $group) {
                try {
                    $translatedGroup = $this->translateTextGroup(
                        $group['texts'],
                        'pt',
                        $targetLang
                    );
                    
                    // Mapeia as traduções de volta para as chaves originais
                    foreach ($group['keys'] as $index => $originalKey) {
                        $translations[$targetLang][$originalKey] = $translatedGroup[$index] ?? $texts[$originalKey];
                    }
                    
                    // Pequena pausa entre requisições para evitar throttling
                    if ($groupIndex < count($textGroups) - 1) {
                        usleep(100000); // 100ms
                    }
                    
                } catch (AwsException $e) {
                    Log::error("Erro na tradução AWS para {$targetLang}: " . $e->getMessage());
                    
                    // Em caso de erro, usa o texto original
                    foreach ($group['keys'] as $originalKey) {
                        $translations[$targetLang][$originalKey] = $texts[$originalKey];
                    }
                }
            }
        }
        
        return $translations;
    }

    /**
     * Agrupa textos para otimizar o número de requisições ao AWS Translate
     * 
     * @param array $texts
     * @return array
     */
    private function groupTexts(array $texts): array
    {
        $groups = [];
        $currentGroup = [
            'texts' => [],
            'keys' => [],
            'size' => 0
        ];
        
        foreach ($texts as $key => $text) {
            $textSize = strlen($text);
            
            // Se adicionar este texto ultrapassar o limite, finaliza o grupo atual
            if ($currentGroup['size'] + $textSize > self::MAX_TEXT_SIZE && !empty($currentGroup['texts'])) {
                $groups[] = $currentGroup;
                $currentGroup = [
                    'texts' => [],
                    'keys' => [],
                    'size' => 0
                ];
            }
            
            $currentGroup['texts'][] = $text;
            $currentGroup['keys'][] = $key;
            $currentGroup['size'] += $textSize;
        }
        
        // Adiciona o último grupo se não estiver vazio
        if (!empty($currentGroup['texts'])) {
            $groups[] = $currentGroup;
        }
        
        return $groups;
    }

    /**
     * Traduz um grupo de textos usando AWS Translate
     * 
     * @param array $texts
     * @param string $sourceLang
     * @param string $targetLang
     * @return array
     */
    private function translateTextGroup(array $texts, string $sourceLang, string $targetLang): array
    {
        $translations = [];
        
        foreach ($texts as $text) {
            try {
                Log::info("Traduzindo: '{$text}' de {$sourceLang} para {$targetLang}");
                
                $result = $this->translateClient->translateText([
                    'SourceLanguageCode' => self::LANGUAGE_MAP[$sourceLang],
                    'TargetLanguageCode' => self::LANGUAGE_MAP[$targetLang],
                    'Text' => $text,
                ]);
                
                $translatedText = $result->get('TranslatedText');
                $translations[] = $translatedText;
                
                Log::info("Tradução obtida: '{$translatedText}'");
                
            } catch (AwsException $e) {
                Log::error("Erro ao traduzir texto individual: " . $e->getMessage());
                $translations[] = $text; // Retorna texto original em caso de erro
            }
        }
        
        return $translations;
    }

    /**
     * Traduz estruturas aninhadas (arrays) mantendo a hierarquia
     * 
     * @param array $data Dados em português
     * @param array $targetLanguages Idiomas de destino
     * @return array Traduções organizadas por idioma
     */
    public function translateNestedData(array $data, array $targetLanguages = ['en', 'es']): array
    {
        // Se não estiver configurado, retorna os dados originais
        if (!$this->isConfigured) {
            $result = [];
            foreach ($targetLanguages as $lang) {
                $result[$lang] = $data;
            }
            return $result;
        }
        
        // Achata a estrutura para coletar todos os textos
        $flatTexts = [];
        $this->flattenArray($data, $flatTexts);
        
        // Traduz todos os textos
        $translations = $this->translateTexts($flatTexts, $targetLanguages);
        
        // Reconstrói a estrutura original para cada idioma
        $result = [];
        foreach ($targetLanguages as $lang) {
            $result[$lang] = $this->rebuildStructure($data, $translations[$lang]);
        }
        
        return $result;
    }

    /**
     * Achata um array aninhado coletando todos os valores de string
     * 
     * @param array $array
     * @param array &$flat
     * @param string $prefix
     */
    private function flattenArray(array $array, array &$flat, string $prefix = ''): void
    {
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                $this->flattenArray($value, $flat, $newKey);
            } else {
                $flat[$newKey] = $value;
            }
        }
    }

    /**
     * Reconstrói a estrutura original usando as traduções
     * 
     * @param array $original
     * @param array $translations
     * @param string $prefix
     * @return array
     */
    private function rebuildStructure(array $original, array $translations, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($original as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                $result[$key] = $this->rebuildStructure($value, $translations, $newKey);
            } else {
                $result[$key] = $translations[$newKey] ?? $value;
            }
        }
        
        return $result;
    }

    /**
     * Verifica se as credenciais AWS estão configuradas
     * 
     * @return bool
     */
    private function checkConfiguration(): bool
    {
        return !empty(config('services.aws.key')) && 
               !empty(config('services.aws.secret'));
    }

    /**
     * Verifica se o serviço AWS está configurado corretamente
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }
}