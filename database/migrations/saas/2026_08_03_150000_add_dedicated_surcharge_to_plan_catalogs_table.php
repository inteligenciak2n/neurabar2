<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    /**
     * O adicional de infraestrutura dedicada existia em `venue_subscriptions`
     * mas nunca era preenchido: não havia origem para o valor. Passa a ser
     * definido no catálogo de planos, em centavos.
     */
    public function up(): void
    {
        Schema::connection('saas')->table('plan_catalogs', function (Blueprint $table): void {
            $table->bigInteger('dedicated_surcharge')->default(0)->after('monthly_price');
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->table('plan_catalogs', function (Blueprint $table): void {
            $table->dropColumn('dedicated_surcharge');
        });
    }
};
