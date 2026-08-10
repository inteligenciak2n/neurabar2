<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('plan_catalog_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('plan_catalog_id')->constrained('plan_catalogs')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status')->default('draft');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->bigInteger('minimum_monthly_price')->default(0);
            $table->string('infrastructure_type')->default('shared');
            $table->string('currency', 3)->default('BRL');
            $table->timestamps();

            $table->unique(['plan_catalog_id', 'version']);
            $table->index(['status', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('plan_catalog_versions');
    }
};
