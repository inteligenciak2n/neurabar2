<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('corporation_subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('corporation_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('plan_catalog_id')->nullable()->constrained('plan_catalogs')->nullOnDelete();
            $table->foreignUuid('affiliate_code_id')->nullable()->constrained('affiliate_codes')->nullOnDelete();
            $table->string('billing_mode')->default('per_venue');
            $table->string('status')->default('trial');
            $table->tinyInteger('billing_day')->default(1);
            $table->tinyInteger('grace_period_days')->default(3);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('currency', 3)->default('BRL');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('corporation_subscriptions');
    }
};
