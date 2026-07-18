<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('payment_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('invoice_type');
            $table->uuid('invoice_id');
            $table->string('gateway')->default('asaas');
            $table->string('gateway_payment_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->json('payload')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('payment_attempts');
    }
};
