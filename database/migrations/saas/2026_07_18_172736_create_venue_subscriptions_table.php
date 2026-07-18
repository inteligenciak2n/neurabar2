<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('venue_subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('corporation_subscription_id')->constrained('corporation_subscriptions')->cascadeOnDelete();
            $table->foreignUuid('plan_catalog_id')->nullable()->constrained('plan_catalogs')->nullOnDelete();
            $table->foreignUuid('affiliate_code_id')->nullable()->constrained('affiliate_codes')->nullOnDelete();
            $table->string('status')->default('trial');
            $table->decimal('base_value', 10, 2)->default(0);
            $table->decimal('modules_value', 10, 2)->default(0);
            $table->decimal('metered_value', 10, 2)->default(0);
            $table->decimal('dedicated_surcharge', 10, 2)->default(0);
            $table->decimal('total_value', 10, 2)->default(0);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('venue_subscriptions');
    }
};
