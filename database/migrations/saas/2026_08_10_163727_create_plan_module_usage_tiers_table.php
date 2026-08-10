<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('plan_module_usage_tiers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('plan_catalog_version_id')->constrained('plan_catalog_versions')->cascadeOnDelete();
            $table->string('module_code');
            $table->integer('min_quantity')->default(0);
            $table->integer('max_quantity')->nullable();
            $table->integer('included_quantity')->default(0);
            $table->bigInteger('price_per_unit')->default(0);
            $table->bigInteger('flat_price')->nullable();
            $table->bigInteger('overage_price_per_unit')->default(0);
            $table->bigInteger('overage_flat_fee')->nullable();
            $table->string('currency', 3)->default('BRL');
            $table->timestamps();

            $table->unique(['plan_catalog_version_id', 'module_code', 'min_quantity'], 'plan_module_tier_start_unique');
            $table->index(['plan_catalog_version_id', 'module_code']);
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('plan_module_usage_tiers');
    }
};
