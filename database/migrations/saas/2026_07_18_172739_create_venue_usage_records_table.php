<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('venue_usage_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('venue_id')->constrained()->cascadeOnDelete();
            $table->string('module_code');
            $table->string('period');
            $table->integer('quantity')->default(0);
            $table->integer('included_quantity')->default(0);
            $table->integer('overage_quantity')->default(0);
            $table->foreignUuid('tier_id')->nullable()->constrained('module_usage_tiers')->nullOnDelete();
            $table->decimal('base_calculated_price', 10, 2)->default(0);
            $table->decimal('overage_calculated_price', 10, 2)->default(0);
            $table->decimal('total_calculated_price', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['venue_id', 'module_code', 'period']);
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('venue_usage_records');
    }
};
