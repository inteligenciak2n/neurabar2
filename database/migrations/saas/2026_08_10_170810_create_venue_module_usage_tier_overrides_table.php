<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('venue_module_usage_tier_overrides', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('venue_plan_assignment_id')->constrained('venue_plan_assignments')->cascadeOnDelete();
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

            $table->unique(['venue_plan_assignment_id', 'module_code', 'min_quantity'], 'venue_module_override_start_unique');
            $table->index(['venue_plan_assignment_id', 'module_code'], 'venue_module_override_lookup');
        });

        Schema::connection('saas')->table('venue_usage_records', function (Blueprint $table): void {
            $table->foreignUuid('venue_module_usage_tier_override_id')
                ->nullable()
                ->after('plan_module_usage_tier_id')
                ->constrained('venue_module_usage_tier_overrides')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->table('venue_usage_records', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('venue_module_usage_tier_override_id');
        });

        Schema::connection('saas')->dropIfExists('venue_module_usage_tier_overrides');
    }
};
