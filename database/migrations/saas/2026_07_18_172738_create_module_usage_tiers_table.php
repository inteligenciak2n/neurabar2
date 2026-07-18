<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('module_usage_tiers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('module_code');
            $table->integer('min_quantity')->default(0);
            $table->integer('max_quantity')->nullable();
            $table->integer('included_quantity')->default(0);
            $table->decimal('price_per_unit', 10, 4)->default(0);
            $table->decimal('flat_price', 10, 2)->nullable();
            $table->decimal('overage_price_per_unit', 10, 4)->default(0);
            $table->decimal('overage_flat_fee', 10, 2)->nullable();
            $table->string('currency', 3)->default('BRL');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('module_usage_tiers');
    }
};
