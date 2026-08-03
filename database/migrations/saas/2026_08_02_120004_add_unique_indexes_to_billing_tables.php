<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Blindagem contra faturas duplicadas.
 *
 * O faturamento roda em jobs concorrentes e reprocessa webhooks do gateway; sem
 * unicidade no banco, duas execuções simultâneas do GenerateInvoicesJob (ou um
 * webhook reentregue) geravam duas faturas cobráveis para o mesmo período.
 *
 * Índices parciais: ignoram linhas soft-deleted e valores nulos.
 */
return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        DB::connection('saas')->statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS venue_invoices_venue_period_unique
             ON venue_invoices (venue_id, period) WHERE deleted_at IS NULL'
        );

        DB::connection('saas')->statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS venue_invoices_gateway_payment_id_unique
             ON venue_invoices (gateway_payment_id) WHERE gateway_payment_id IS NOT NULL AND deleted_at IS NULL'
        );

        DB::connection('saas')->statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS corporation_invoices_corporation_period_unique
             ON corporation_invoices (corporation_id, period) WHERE deleted_at IS NULL'
        );

        DB::connection('saas')->statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS corporation_invoices_gateway_payment_id_unique
             ON corporation_invoices (gateway_payment_id) WHERE gateway_payment_id IS NOT NULL AND deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        foreach ([
            'venue_invoices_venue_period_unique',
            'venue_invoices_gateway_payment_id_unique',
            'corporation_invoices_corporation_period_unique',
            'corporation_invoices_gateway_payment_id_unique',
        ] as $index) {
            DB::connection('saas')->statement("DROP INDEX IF EXISTS {$index}");
        }
    }
};
