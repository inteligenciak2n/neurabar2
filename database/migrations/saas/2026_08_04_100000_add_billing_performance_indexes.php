<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    /**
     * Os jobs de faturamento varrem faturas e assinaturas por período, status e
     * vencimento todos os dias. Sem índice, cada execução é um sequential scan
     * na tabela inteira — o custo cresce com a base de clientes.
     */
    public function up(): void
    {
        Schema::connection('saas')->table('venue_invoices', function (Blueprint $table): void {
            $table->index('period', 'venue_invoices_period_index');
            $table->index(['status', 'due_date', 'is_finalized'], 'venue_invoices_status_due_date_index');
            $table->index(['venue_id', 'period'], 'venue_invoices_venue_period_index');
        });

        Schema::connection('saas')->table('corporation_invoices', function (Blueprint $table): void {
            $table->index('period', 'corporation_invoices_period_index');
            $table->index(['status', 'due_date', 'is_finalized'], 'corporation_invoices_status_due_date_index');
            $table->index(['corporation_id', 'period'], 'corporation_invoices_corporation_period_index');
        });

        Schema::connection('saas')->table('venue_usage_records', function (Blueprint $table): void {
            $table->index('period', 'venue_usage_records_period_index');
        });

        Schema::connection('saas')->table('corporation_subscriptions', function (Blueprint $table): void {
            $table->index(['status', 'trial_ends_at'], 'corporation_subscriptions_status_trial_index');
        });

        Schema::connection('saas')->table('venue_subscriptions', function (Blueprint $table): void {
            $table->index(['status', 'trial_ends_at'], 'venue_subscriptions_status_trial_index');
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->table('venue_invoices', function (Blueprint $table): void {
            $table->dropIndex('venue_invoices_period_index');
            $table->dropIndex('venue_invoices_status_due_date_index');
            $table->dropIndex('venue_invoices_venue_period_index');
        });

        Schema::connection('saas')->table('corporation_invoices', function (Blueprint $table): void {
            $table->dropIndex('corporation_invoices_period_index');
            $table->dropIndex('corporation_invoices_status_due_date_index');
            $table->dropIndex('corporation_invoices_corporation_period_index');
        });

        Schema::connection('saas')->table('venue_usage_records', function (Blueprint $table): void {
            $table->dropIndex('venue_usage_records_period_index');
        });

        Schema::connection('saas')->table('corporation_subscriptions', function (Blueprint $table): void {
            $table->dropIndex('corporation_subscriptions_status_trial_index');
        });

        Schema::connection('saas')->table('venue_subscriptions', function (Blueprint $table): void {
            $table->dropIndex('venue_subscriptions_status_trial_index');
        });
    }
};
