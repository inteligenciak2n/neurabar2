<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Converte todo o dinheiro do domínio de cobrança de `decimal` para inteiro.
 *
 * Float/decimal-string obrigava cada consumidor a fazer o próprio cast e o
 * próprio arredondamento, e a soma das parcelas proporcionais divergia do total
 * gravado. A partir daqui a unidade é o centavo, exceto nos preços unitários de
 * consumo medido, que preservam as 4 casas decimais atuais guardando centésimos
 * de centavo.
 *
 * `corporation_discounts.value` é polimórfica: guarda reais quando
 * `type = 'fixed'` e percentual quando `type = 'percentage'`. A escala de 100
 * atende às duas — R$ 50,00 vira 5000 centavos e 15% vira 1500 pontos-base.
 */
return new class extends Migration
{
    protected $connection = 'saas';

    /**
     * Colunas monetárias por tabela, com o multiplicador aplicado na conversão.
     *
     * @var array<string, array<string, int>>
     */
    private const COLUMNS = [
        'plan_catalogs' => ['monthly_price' => 100],
        'module_catalogs' => ['base_monthly_price' => 100],
        'corporation_modules' => ['custom_monthly_price' => 100],
        'corporation_discounts' => ['value' => 100],
        'venue_subscriptions' => [
            'base_value' => 100,
            'modules_value' => 100,
            'metered_value' => 100,
            'dedicated_surcharge' => 100,
            'total_value' => 100,
        ],
        'venue_invoices' => [
            'base_value' => 100,
            'modules_value' => 100,
            'metered_value' => 100,
            'dedicated_surcharge' => 100,
            'discount_value' => 100,
            'total_value' => 100,
        ],
        'corporation_invoices' => [
            'base_value' => 100,
            'modules_value' => 100,
            'metered_value' => 100,
            'dedicated_surcharge' => 100,
            'discount_value' => 100,
            'total_value' => 100,
        ],
        'venue_usage_records' => [
            'base_calculated_price' => 100,
            'overage_calculated_price' => 100,
            'total_calculated_price' => 100,
        ],
        'module_usage_tiers' => [
            'price_per_unit' => 10000,
            'flat_price' => 100,
            'overage_price_per_unit' => 10000,
            'overage_flat_fee' => 100,
        ],
        'payment_attempts' => ['amount' => 100],
    ];

    /**
     * Colunas que voltam a ter `DEFAULT 0` depois da troca de tipo.
     *
     * @var array<string, list<string>>
     */
    private const DEFAULT_ZERO = [
        'module_catalogs' => ['base_monthly_price'],
        'venue_subscriptions' => ['base_value', 'modules_value', 'metered_value', 'dedicated_surcharge', 'total_value'],
        'venue_invoices' => ['base_value', 'modules_value', 'metered_value', 'dedicated_surcharge', 'discount_value', 'total_value'],
        'corporation_invoices' => ['base_value', 'modules_value', 'metered_value', 'dedicated_surcharge', 'discount_value', 'total_value'],
        'venue_usage_records' => ['base_calculated_price', 'overage_calculated_price', 'total_calculated_price'],
        'module_usage_tiers' => ['price_per_unit', 'overage_price_per_unit'],
        'payment_attempts' => ['amount'],
    ];

    /**
     * Precisão original de cada coluna, usada no rollback.
     *
     * @var array<string, array<string, string>>
     */
    private const ORIGINAL_TYPES = [
        'corporation_discounts' => ['value' => 'numeric(12,2)'],
        'module_usage_tiers' => [
            'price_per_unit' => 'numeric(10,4)',
            'overage_price_per_unit' => 'numeric(10,4)',
        ],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column => $multiplier) {
                $this->dropDefault($table, $column);

                DB::connection($this->connection)->statement(
                    sprintf(
                        'ALTER TABLE %s ALTER COLUMN %s TYPE bigint USING round(%s * %d)::bigint',
                        $this->quote($table),
                        $this->quote($column),
                        $this->quote($column),
                        $multiplier
                    )
                );

                $this->restoreDefault($table, $column);
            }
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column => $multiplier) {
                $this->dropDefault($table, $column);

                DB::connection($this->connection)->statement(
                    sprintf(
                        'ALTER TABLE %s ALTER COLUMN %s TYPE %s USING (%s::numeric / %d)',
                        $this->quote($table),
                        $this->quote($column),
                        self::ORIGINAL_TYPES[$table][$column] ?? 'numeric(10,2)',
                        $this->quote($column),
                        $multiplier
                    )
                );

                $this->restoreDefault($table, $column);
            }
        }
    }

    private function dropDefault(string $table, string $column): void
    {
        DB::connection($this->connection)->statement(
            sprintf('ALTER TABLE %s ALTER COLUMN %s DROP DEFAULT', $this->quote($table), $this->quote($column))
        );
    }

    private function restoreDefault(string $table, string $column): void
    {
        if (! in_array($column, self::DEFAULT_ZERO[$table] ?? [], true)) {
            return;
        }

        DB::connection($this->connection)->statement(
            sprintf('ALTER TABLE %s ALTER COLUMN %s SET DEFAULT 0', $this->quote($table), $this->quote($column))
        );
    }

    private function quote(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
};
