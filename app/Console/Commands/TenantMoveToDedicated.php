<?php

namespace App\Console\Commands;

use App\Models\Tenant\Corporation;
use App\Services\TenantConnectionResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class TenantMoveToDedicated extends Command
{
    protected $signature = 'tenant:move-to-dedicated {corporation_id : UUID da Corporation}';

    protected $description = 'Move um tenant de banco compartilhado para banco dedicado';

    public function __construct(private readonly TenantConnectionResolver $resolver)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $corporationId = $this->argument('corporation_id');

        // ── Step A: Validar ───────────────────────────────────────────────────
        $corporation = Corporation::on('saas')->with('venues')->find($corporationId);

        if (! $corporation) {
            $this->components->error("Corporation [{$corporationId}] não encontrada.");

            return self::FAILURE;
        }

        if ($corporation->is_dedicated) {
            $this->components->warn("Corporation [{$corporation->name}] já está em banco dedicado ({$corporation->self_connection}).");

            return self::SUCCESS;
        }

        $sharedConnection = $corporation->self_connection ?? 'operation_default_1';
        $dedicatedConnection = TenantConnectionResolver::dedicatedConnectionName($corporationId);
        $venueIds = $corporation->venues->pluck('id')->toArray();

        if (empty($venueIds)) {
            $this->components->error('Corporation não possui venues.');

            return self::FAILURE;
        }

        $this->components->info("Iniciando migração de [{$corporation->name}]");
        $this->components->bulletList([
            "Banco de origem: {$sharedConnection}",
            "Banco de destino: {$dedicatedConnection}",
            'Venues: '.implode(', ', $venueIds),
        ]);

        if (! $this->confirm('Confirma a migração?', true)) {
            return self::SUCCESS;
        }

        // ── Step B: Criar banco dedicado ──────────────────────────────────────
        $this->components->task('Criando banco de dados dedicado', function () use ($dedicatedConnection): void {
            $this->createDatabase($dedicatedConnection);
        });

        // ── Step C: Registrar conexão e rodar migrations ──────────────────────
        $this->resolver->registerDedicatedConnection($dedicatedConnection);

        $this->components->task('Rodando migrations no banco dedicado', function () use ($dedicatedConnection): void {
            $exitCode = $this->call('migrate', [
                '--database' => $dedicatedConnection,
                '--path' => 'database/migrations/operational',
                '--no-interaction' => true,
                '--force' => true,
            ]);

            if ($exitCode !== self::SUCCESS) {
                throw new \RuntimeException('Migrate falhou no banco dedicado.');
            }
        });

        // ── Steps D + E + F: Copiar dados, deletar e atualizar (atomicamente onde possível) ──
        try {
            $this->components->info('Copiando dados do banco compartilhado para o dedicado...');
            $this->copyTenantData($sharedConnection, $dedicatedConnection, $venueIds);

            $this->components->info('Removendo dados do banco compartilhado e atualizando mapeamento...');
            $this->commitMigration($corporation, $sharedConnection, $dedicatedConnection, $venueIds);

        } catch (Throwable $e) {
            $this->components->error("Erro durante a migração: {$e->getMessage()}");
            $this->components->warn('Tentando rollback: dropando banco dedicado...');
            $this->dropDatabase($dedicatedConnection);

            return self::FAILURE;
        }

        // ── Step G: Invalidar cache ───────────────────────────────────────────
        $this->components->task('Invalidando cache Redis', function () use ($corporation): void {
            $this->resolver->invalidateCorporation($corporation->id);
        });

        $this->newLine();
        $this->components->success("Tenant [{$corporation->name}] migrado com sucesso para [{$dedicatedConnection}].");

        return self::SUCCESS;
    }

    /**
     * Tabelas operacionais em ordem de dependência (respeita FKs).
     * As tabelas pai devem ser copiadas antes das filhas.
     */
    private function operationalTables(): array
    {
        return [
            'venue_settings',
            'kitchen_stations',
            'preparation_statuses',
            'service_locations',
            'attendance_channels',
            'menus',
            'menu_categories',
            'products',
            'product_variations',
            'modifier_groups',
            'modifier_options',
            'product_modifier_group',
            'combos',
            'combo_items',
            'attendances',
            'orders',
            'order_items',
            'order_item_modifiers',
            'payments',
            'payment_items',
        ];
    }

    /**
     * Copia todos os dados do tenant do banco compartilhado para o dedicado.
     * Tabelas que possuem venue_id são filtradas diretamente.
     * Tabelas que referenciam outras pelo attendance_id são resolvidas via subquery.
     */
    private function copyTenantData(string $from, string $to, array $venueIds): void
    {
        // Tabelas com venue_id direto
        $tablesWithVenueId = [
            'venue_settings', 'kitchen_stations', 'preparation_statuses',
            'service_locations', 'attendance_channels', 'menus', 'menu_categories',
            'products', 'product_variations', 'modifier_groups', 'modifier_options',
            'product_modifier_group', 'combos', 'combo_items', 'attendances',
            'payments',
        ];

        foreach ($tablesWithVenueId as $table) {
            $rows = DB::connection($from)->table($table)
                ->whereIn('venue_id', $venueIds)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->toArray();

            if (! empty($rows)) {
                DB::connection($to)->table($table)->insert($rows);
                $this->line("  → {$table}: ".count($rows).' linhas');
            }
        }

        // orders: via attendance_id (attendances já foram copiadas)
        $attendanceIds = DB::connection($to)->table('attendances')->pluck('id')->toArray();

        if (! empty($attendanceIds)) {
            $orders = DB::connection($from)->table('orders')
                ->whereIn('attendance_id', $attendanceIds)
                ->get()->map(fn ($r) => (array) $r)->toArray();

            if (! empty($orders)) {
                DB::connection($to)->table('orders')->insert($orders);
                $this->line('  → orders: '.count($orders).' linhas');

                $orderIds = DB::connection($to)->table('orders')->pluck('id')->toArray();

                // order_items: via order_id
                $orderItems = DB::connection($from)->table('order_items')
                    ->whereIn('order_id', $orderIds)
                    ->get()->map(fn ($r) => (array) $r)->toArray();

                if (! empty($orderItems)) {
                    DB::connection($to)->table('order_items')->insert($orderItems);
                    $this->line('  → order_items: '.count($orderItems).' linhas');

                    $orderItemIds = DB::connection($to)->table('order_items')->pluck('id')->toArray();

                    // order_item_modifiers: via order_item_id
                    $modifiers = DB::connection($from)->table('order_item_modifiers')
                        ->whereIn('order_item_id', $orderItemIds)
                        ->get()->map(fn ($r) => (array) $r)->toArray();

                    if (! empty($modifiers)) {
                        DB::connection($to)->table('order_item_modifiers')->insert($modifiers);
                        $this->line('  → order_item_modifiers: '.count($modifiers).' linhas');
                    }
                }
            }

            // payment_items: via payment_id (payments já foram copiados)
            $paymentIds = DB::connection($to)->table('payments')->pluck('id')->toArray();
            if (! empty($paymentIds)) {
                $paymentItems = DB::connection($from)->table('payment_items')
                    ->whereIn('payment_id', $paymentIds)
                    ->get()->map(fn ($r) => (array) $r)->toArray();

                if (! empty($paymentItems)) {
                    DB::connection($to)->table('payment_items')->insert($paymentItems);
                    $this->line('  → payment_items: '.count($paymentItems).' linhas');
                }
            }
        }
    }

    /**
     * Dentro de uma transação no banco compartilhado:
     *   1. Deleta os dados do tenant (na ordem inversa das FKs)
     *   2. Atualiza corporation.self_connection e is_dedicated no saas
     */
    private function commitMigration(
        Corporation $corporation,
        string $sharedConnection,
        string $dedicatedConnection,
        array $venueIds
    ): void {
        DB::connection($sharedConnection)->transaction(function () use (
            $sharedConnection, $venueIds
        ): void {
            // Deletar na ordem inversa das FKs
            $attendanceIds = DB::connection($sharedConnection)->table('attendances')
                ->whereIn('venue_id', $venueIds)->pluck('id')->toArray();

            if (! empty($attendanceIds)) {
                $orderIds = DB::connection($sharedConnection)->table('orders')
                    ->whereIn('attendance_id', $attendanceIds)->pluck('id')->toArray();

                if (! empty($orderIds)) {
                    $orderItemIds = DB::connection($sharedConnection)->table('order_items')
                        ->whereIn('order_id', $orderIds)->pluck('id')->toArray();

                    if (! empty($orderItemIds)) {
                        DB::connection($sharedConnection)->table('order_item_modifiers')
                            ->whereIn('order_item_id', $orderItemIds)->delete();
                        DB::connection($sharedConnection)->table('order_items')
                            ->whereIn('order_id', $orderIds)->delete();
                    }

                    DB::connection($sharedConnection)->table('orders')
                        ->whereIn('attendance_id', $attendanceIds)->delete();
                }
            }

            $paymentIds = DB::connection($sharedConnection)->table('payments')
                ->whereIn('venue_id', $venueIds)->pluck('id')->toArray();
            if (! empty($paymentIds)) {
                DB::connection($sharedConnection)->table('payment_items')
                    ->whereIn('payment_id', $paymentIds)->delete();
                DB::connection($sharedConnection)->table('payments')
                    ->whereIn('venue_id', $venueIds)->delete();
            }

            DB::connection($sharedConnection)->table('attendances')
                ->whereIn('venue_id', $venueIds)->delete();

            foreach (array_reverse($this->operationalTables()) as $table) {
                if (! in_array($table, ['orders', 'order_items', 'order_item_modifiers', 'attendances', 'payments', 'payment_items'])) {
                    DB::connection($sharedConnection)->table($table)
                        ->whereIn('venue_id', $venueIds)->delete();
                }
            }
        });

        // Atualizar mapeamento no saas (fora da transação operacional, mas no saas)
        DB::connection('saas')->transaction(function () use ($corporation, $dedicatedConnection): void {
            $corporation->update([
                'self_connection' => $dedicatedConnection,
                'is_dedicated' => true,
            ]);
        });
    }

    private function createDatabase(string $databaseName): void
    {
        $adminConnection = DB::connection('saas');

        $pdo = $adminConnection->getPdo();

        // Verificar se já existe
        $exists = $pdo->query(
            "SELECT 1 FROM pg_database WHERE datname = '{$databaseName}'"
        )->fetchColumn();

        if (! $exists) {
            // Não pode criar banco dentro de transação no PostgreSQL
            $pdo->exec("CREATE DATABASE \"{$databaseName}\"");
            $this->line("  → Banco [{$databaseName}] criado.");
        } else {
            $this->line("  → Banco [{$databaseName}] já existe.");
        }
    }

    private function dropDatabase(string $databaseName): void
    {
        try {
            $pdo = DB::connection('saas')->getPdo();

            // Forçar desconexão de todos os clientes antes de dropar
            $pdo->exec("
                SELECT pg_terminate_backend(pid)
                FROM pg_stat_activity
                WHERE datname = '{$databaseName}' AND pid <> pg_backend_pid()
            ");

            $pdo->exec("DROP DATABASE IF EXISTS \"{$databaseName}\"");
            $this->line("  → Banco [{$databaseName}] removido.");
        } catch (Throwable $e) {
            $this->components->error("Não foi possível dropar [{$databaseName}]: {$e->getMessage()}");
        }
    }
}
