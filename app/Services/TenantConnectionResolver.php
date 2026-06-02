<?php

namespace App\Services;

use App\Models\Tenant\Venue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class TenantConnectionResolver
{
    /**
     * Prefixo usado no Redis para evitar colisão de chaves.
     */
    private const CACHE_PREFIX = 'venue_connection:';

    /**
     * TTL do cache Redis em segundos (60 minutos).
     */
    private const CACHE_TTL = 3600;

    /**
     * Resolve o nome da conexão operacional para um Venue.
     *
     * - Tenant compartilhado: retorna o nome armazenado em corporation->self_connection
     *   (ex.: "operation_default_1")
     * - Tenant dedicado: garante que a conexão dinâmica está registrada em runtime
     *   via config()->set() e retorna o nome da conexão (ex.: "operation_tenant_{corp_id}")
     *
     * O resultado é cacheado no Redis para evitar queries extras por request.
     */
    public function resolve(Venue $venue): string
    {
        $cacheKey = self::CACHE_PREFIX.$venue->id;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($venue): string {
            $corporation = $venue->corporation;

            $connectionName = $corporation->self_connection ?? 'operation_default_1';

            if ($corporation->is_dedicated) {
                $this->registerDedicatedConnection($connectionName);
            }

            return $connectionName;
        });
    }

    /**
     * Registra uma conexão dedicada em runtime no config do Laravel.
     *
     * Usa as mesmas credenciais base dos bancos operacionais, alterando apenas
     * o database name. O Laravel reutiliza a instância de conexão aberta pelo
     * PDO no mesmo request, evitando overhead de reconexão.
     *
     * Padrão do nome do banco: operation_tenant_{short_corporation_id}
     * Padrão do nome da conexão: operation_tenant_{short_corporation_id}
     */
    public function registerDedicatedConnection(string $connectionName): void
    {
        // Conexão já registrada neste request — não precisa recriar
        if (Config::has("database.connections.{$connectionName}")) {
            return;
        }

        Config::set("database.connections.{$connectionName}", [
            'driver' => 'pgsql',
            'host' => env('DB_OP_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('DB_OP_PORT', env('DB_PORT', '5432')),
            'database' => $connectionName,
            'username' => env('DB_OP_USERNAME', env('DB_USERNAME', 'sail')),
            'password' => env('DB_OP_PASSWORD', env('DB_PASSWORD', '')),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ]);
    }

    /**
     * Remove do cache Redis o mapeamento de conexão para um Venue.
     *
     * Deve ser chamado após:
     * - Mover um tenant de compartilhado para dedicado (tenant:move-to-dedicated)
     * - Alterar manualmente corporation->self_connection
     */
    public function invalidate(Venue $venue): void
    {
        Cache::forget(self::CACHE_PREFIX.$venue->id);
    }

    /**
     * Invalida o cache de todos os venues de uma corporation.
     */
    public function invalidateCorporation(int|string $corporationId): void
    {
        // Busca os venues da corporation para limpar individualmente
        // (necessário pois o cache é keyed por venue_id)
        $venues = Venue::on('saas')
            ->where('corporation_id', $corporationId)
            ->select('id')
            ->get();

        foreach ($venues as $venue) {
            Cache::forget(self::CACHE_PREFIX.$venue->id);
        }
    }

    /**
     * Deriva o nome da conexão dedicada para uma corporation.
     * Padrão: operation_tenant_{primeiros 8 chars do UUID da corporation}
     */
    public static function dedicatedConnectionName(string $corporationId): string
    {
        // UUID sem hífens, primeiros 12 chars — suficientemente único e legível
        $slug = substr(str_replace('-', '', $corporationId), 0, 12);

        return "operation_tenant_{$slug}";
    }
}
