<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\Languages\AwsTranslationService;

class UpdateTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:translations {--no-aws : Não usar AWS Translate e apenas copiar textos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza arquivos de tradução mapeando pt para en e es, usando AWS Translate ou copiando textos';

    /**
     * @var AwsTranslationService
     */
    private $awsTranslationService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando atualização dos arquivos de tradução...');
        
        // Inicializa o service de tradução AWS
        $this->awsTranslationService = new AwsTranslationService();
        $useAwsTranslate = !$this->option('no-aws') && $this->awsTranslationService->isConfigured();
        
        if ($useAwsTranslate) {
            $this->info('🌐 Usando AWS Translate para traduções automáticas');
        } else {
            if (!$this->awsTranslationService->isConfigured()) {
                $this->warn('⚠️  AWS não configurado. Copiando textos em português.');
            } else {
                $this->info('📝 Modo manual ativado. Copiando textos em português.');
            }
        }
        
        $ptPath = resource_path('translations/pt');
        $enPath = resource_path('translations/en');
        $esPath = resource_path('translations/es');
        
        // Verifica se a pasta pt existe
        if (!File::exists($ptPath)) {
            $this->error('Pasta de traduções em português não encontrada: ' . $ptPath);
            return Command::FAILURE;
        }
        
        // Cria a pasta en se não existir
        if (!File::exists($enPath)) {
            File::makeDirectory($enPath, 0755, true);
            $this->info('Pasta de traduções em inglês criada: ' . $enPath);
        }
        
        // Cria a pasta es se não existir
        if (!File::exists($esPath)) {
            File::makeDirectory($esPath, 0755, true);
            $this->info('Pasta de traduções em espanhol criada: ' . $esPath);
        }
        
        // Mapeia todos os arquivos JSON da pasta pt
        $ptFiles = File::glob($ptPath . '/*.json');
        
        $this->info('Encontrados ' . count($ptFiles) . ' arquivos de tradução em português.');
        
        $enStats = ['created' => 0, 'updated' => 0, 'keysAdded' => 0];
        $esStats = ['created' => 0, 'updated' => 0, 'keysAdded' => 0];
        
        foreach ($ptFiles as $ptFile) {
            $fileName = basename($ptFile);
            $enFile = $enPath . '/' . $fileName;
            $esFile = $esPath . '/' . $fileName;
            
            $this->info('Processando arquivo: ' . $fileName);
            
            // Lê o conteúdo do arquivo pt
            $ptContent = File::get($ptFile);
            $ptData = json_decode($ptContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Erro ao decodificar JSON do arquivo: ' . $fileName);
                continue;
            }
            
            if ($useAwsTranslate) {
                // Usa AWS Translate para traduções automáticas
                $this->processFileWithAwsTranslate($ptData, $enFile, $esFile, $fileName, $enStats, $esStats);
            } else {
                // Processa arquivos copiando texto original (modo antigo)
                $this->processLanguageFile($ptData, $enFile, $fileName, 'inglês', $enStats);
                $this->processLanguageFile($ptData, $esFile, $fileName, 'espanhol', $esStats);
            }
        }
        
        $this->newLine();
        $this->info('Atualização concluída!');
        
        $this->info('=== INGLÊS ===');
        $this->info('Arquivos criados: ' . $enStats['created']);
        $this->info('Arquivos atualizados: ' . $enStats['updated']);
        $this->info('Total de chaves adicionadas: ' . $enStats['keysAdded']);
        
        $this->info('=== ESPANHOL ===');
        $this->info('Arquivos criados: ' . $esStats['created']);
        $this->info('Arquivos atualizados: ' . $esStats['updated']);
        $this->info('Total de chaves adicionadas: ' . $esStats['keysAdded']);
        
        return Command::SUCCESS;
    }
    
    /**
     * Processa arquivos usando AWS Translate para traduções automáticas
     */
    private function processFileWithAwsTranslate(array $ptData, string $enFile, string $esFile, string $fileName, array &$enStats, array &$esStats): void
    {
        try {
            // Coleta textos novos que precisam ser traduzidos
            $newTextsEn = $this->getNewTextsForTranslation($ptData, $enFile);
            $newTextsEs = $this->getNewTextsForTranslation($ptData, $esFile);
            
            $translations = [];
            
            // Se há textos novos para traduzir
            if (!empty($newTextsEn) || !empty($newTextsEs)) {
                $this->info('  🔄 Traduzindo textos novos...');
                
                // Coleta todos os textos únicos que precisam ser traduzidos
                $allNewTexts = array_unique(array_merge($newTextsEn, $newTextsEs));
                
                if (!empty($allNewTexts)) {
                    $translations = $this->awsTranslationService->translateNestedData([
                        'texts' => $allNewTexts
                    ], ['en', 'es']);
                }
            }
            
            // Processa arquivo inglês
            if (!empty($newTextsEn)) {
                $translatedTexts = [];
                foreach ($newTextsEn as $key => $text) {
                    $translatedTexts[$key] = $translations['en']['texts'][array_search($text, $allNewTexts)] ?? $text;
                }
                $this->updateFileWithTranslations($ptData, $enFile, $fileName, 'inglês', $translatedTexts, $enStats);
            } else {
                $this->syncExistingFile($ptData, $enFile, $fileName, 'inglês', $enStats);
            }
            
            // Processa arquivo espanhol
            if (!empty($newTextsEs)) {
                $translatedTexts = [];
                foreach ($newTextsEs as $key => $text) {
                    $translatedTexts[$key] = $translations['es']['texts'][array_search($text, $allNewTexts)] ?? $text;
                }
                $this->updateFileWithTranslations($ptData, $esFile, $fileName, 'espanhol', $translatedTexts, $esStats);
            } else {
                $this->syncExistingFile($ptData, $esFile, $fileName, 'espanhol', $esStats);
            }
            
        } catch (\Exception $e) {
            $this->error('  ❌ Erro na tradução: ' . $e->getMessage());
            $this->warn('  📝 Alternando para modo de cópia...');
            
            // Em caso de erro, usa o método antigo
            $this->processLanguageFile($ptData, $enFile, $fileName, 'inglês', $enStats);
            $this->processLanguageFile($ptData, $esFile, $fileName, 'espanhol', $esStats);
        }
    }
    
    /**
     * Identifica textos novos que precisam ser traduzidos
     */
    private function getNewTextsForTranslation(array $ptData, string $targetFile): array
    {
        if (!File::exists($targetFile)) {
            // Se o arquivo não existe, todos os textos são novos
            $newTexts = [];
            $this->collectAllTexts($ptData, $newTexts);
            return $newTexts;
        }
        
        // Se o arquivo existe, verifica quais chaves são novas
        $targetContent = File::get($targetFile);
        $targetData = json_decode($targetContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Se há erro no JSON existente, trata como arquivo novo
            $newTexts = [];
            $this->collectAllTexts($ptData, $newTexts);
            return $newTexts;
        }
        
        return $this->findMissingTexts($ptData, $targetData);
    }
    
    /**
     * Coleta todos os textos de uma estrutura aninhada
     */
    private function collectAllTexts(array $data, array &$texts, string $prefix = ''): void
    {
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                $this->collectAllTexts($value, $texts, $fullKey);
            } else {
                $texts[$fullKey] = $value;
            }
        }
    }
    
    /**
     * Encontra textos que estão em ptData mas não em targetData
     */
    private function findMissingTexts(array $ptData, array $targetData, string $prefix = ''): array
    {
        $missingTexts = [];
        
        foreach ($ptData as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                if (!isset($targetData[$key]) || !is_array($targetData[$key])) {
                    // Se a chave não existe ou não é array, coleta todos os textos
                    $this->collectAllTexts($value, $missingTexts, $fullKey);
                } else {
                    // Se existe e é array, verifica recursivamente
                    $missing = $this->findMissingTexts($value, $targetData[$key], $fullKey);
                    $missingTexts = array_merge($missingTexts, $missing);
                }
            } else {
                if (!isset($targetData[$key])) {
                    $missingTexts[$fullKey] = $value;
                }
            }
        }
        
        return $missingTexts;
    }
    
    /**
     * Atualiza arquivo com traduções específicas
     */
    private function updateFileWithTranslations(array $ptData, string $targetFile, string $fileName, string $language, array $translations, array &$stats): void
    {
        if (!File::exists($targetFile)) {
            // Cria arquivo novo com todas as traduções
            $targetData = $this->applyTranslationsToStructure($ptData, $translations);
            File::put($targetFile, json_encode($targetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info('  ✅ Arquivo ' . $language . ' criado com traduções: ' . $fileName);
            $stats['created']++;
            $stats['keysAdded'] += count($translations);
        } else {
            // Atualiza arquivo existente
            $targetContent = File::get($targetFile);
            $targetData = json_decode($targetContent, true);
            
            $this->mergeTranslationsIntoStructure($ptData, $targetData, $translations);
            
            File::put($targetFile, json_encode($targetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info('  ✅ Arquivo ' . $language . ' atualizado com traduções: ' . $fileName . ' (' . count($translations) . ' traduções)');
            $stats['updated']++;
            $stats['keysAdded'] += count($translations);
        }
    }
    
    /**
     * Aplica traduções a uma estrutura de dados
     */
    private function applyTranslationsToStructure(array $structure, array $translations, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($structure as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                $result[$key] = $this->applyTranslationsToStructure($value, $translations, $fullKey);
            } else {
                $result[$key] = $translations[$fullKey] ?? $value;
            }
        }
        
        return $result;
    }
    
    /**
     * Mescla traduções em estrutura existente
     */
    private function mergeTranslationsIntoStructure(array $ptStructure, array &$targetStructure, array $translations, string $prefix = ''): void
    {
        foreach ($ptStructure as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                if (!isset($targetStructure[$key])) {
                    $targetStructure[$key] = [];
                }
                $this->mergeTranslationsIntoStructure($value, $targetStructure[$key], $translations, $fullKey);
            } else {
                if (!isset($targetStructure[$key]) && isset($translations[$fullKey])) {
                    $targetStructure[$key] = $translations[$fullKey];
                } elseif (!isset($targetStructure[$key])) {
                    $targetStructure[$key] = $value; // Fallback para texto original
                }
            }
        }
    }
    
    /**
     * Sincroniza arquivo existente sem tradução (apenas estrutura)
     */
    private function syncExistingFile(array $ptData, string $targetFile, string $fileName, string $language, array &$stats): void
    {
        if (!File::exists($targetFile)) {
            return;
        }
        
        $targetContent = File::get($targetFile);
        $targetData = json_decode($targetContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }
        
        $keysAdded = $this->syncTranslationKeys($ptData, $targetData);
        
        if ($keysAdded > 0) {
            File::put($targetFile, json_encode($targetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info('  ✅ Arquivo ' . $language . ' sincronizado: ' . $fileName . ' (' . $keysAdded . ' chaves)');
            $stats['updated']++;
            $stats['keysAdded'] += $keysAdded;
        } else {
            $this->info('  ✅ Arquivo ' . $language . ' já está sincronizado: ' . $fileName);
        }
    }
    
    /**
     * Processa um arquivo de tradução para um idioma específico (método original)
     */
    private function processLanguageFile(array $ptData, string $targetFile, string $fileName, string $language, array &$stats): void
    {
        // Se o arquivo não existe, cria uma cópia
        if (!File::exists($targetFile)) {
            $targetData = $this->createTargetTranslations($ptData);
            File::put($targetFile, json_encode($targetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info('  ✓ Arquivo ' . $language . ' criado: ' . $fileName);
            $stats['created']++;
            $stats['keysAdded'] += $this->countKeys($ptData);
        } else {
            // Se existe, sincroniza as chaves
            $targetContent = File::get($targetFile);
            $targetData = json_decode($targetContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Erro ao decodificar JSON do arquivo ' . $language . ': ' . $fileName);
                return;
            }
            
            $keysAdded = $this->syncTranslationKeys($ptData, $targetData);
            
            if ($keysAdded > 0) {
                File::put($targetFile, json_encode($targetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->info('  ✓ Arquivo ' . $language . ' atualizado: ' . $fileName . ' (' . $keysAdded . ' chaves adicionadas)');
                $stats['updated']++;
                $stats['keysAdded'] += $keysAdded;
            } else {
                $this->info('  ✓ Arquivo ' . $language . ' já está sincronizado: ' . $fileName);
            }
        }
    }
    
    /**
     * Cria traduções no idioma de destino baseadas no arquivo português
     */
    private function createTargetTranslations(array $ptData): array
    {
        $targetData = [];
        
        foreach ($ptData as $key => $value) {
            if (is_array($value)) {
                $targetData[$key] = $this->createTargetTranslations($value);
            } else {
                $targetData[$key] = $value;
            }
        }
        
        return $targetData;
    }
    
    /**
     * Sincroniza chaves entre arquivos pt e idioma de destino
     */
    private function syncTranslationKeys(array $ptData, array &$targetData): int
    {
        $keysAdded = 0;
        
        foreach ($ptData as $key => $value) {
            if (!array_key_exists($key, $targetData)) {
                if (is_array($value)) {
                    $targetData[$key] = $this->createTargetTranslations($value);
                    $keysAdded += $this->countKeys($value);
                } else {
                    $targetData[$key] = $value;
                    $keysAdded++;
                }
            } elseif (is_array($value) && is_array($targetData[$key])) {
                // Se ambos são arrays, sincroniza recursivamente
                $keysAdded += $this->syncTranslationKeys($value, $targetData[$key]);
            }
        }
        
        return $keysAdded;
    }
    
    /**
     * Conta o número total de chaves em um array (recursivamente)
     */
    private function countKeys(array $data): int
    {
        $count = 0;
        
        foreach ($data as $value) {
            if (is_array($value)) {
                $count += $this->countKeys($value);
            } else {
                $count++;
            }
        }
        
        return $count;
    }
}
