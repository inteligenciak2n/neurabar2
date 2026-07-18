<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('venue_invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('venue_subscription_id')->nullable()->constrained('venue_subscriptions')->nullOnDelete();
            $table->foreignUuid('corporation_invoice_id')->nullable()->constrained('corporation_invoices')->nullOnDelete();
            $table->foreignUuid('affiliate_code_id')->nullable()->constrained('affiliate_codes')->nullOnDelete();
            $table->string('period');
            $table->date('due_date');
            $table->string('status')->default('open');
            $table->boolean('is_finalized')->default(false);
            $table->decimal('base_value', 10, 2)->default(0);
            $table->decimal('modules_value', 10, 2)->default(0);
            $table->decimal('metered_value', 10, 2)->default(0);
            $table->decimal('dedicated_surcharge', 10, 2)->default(0);
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('total_value', 10, 2)->default(0);
            $table->string('gateway_payment_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('venue_invoices');
    }
};
