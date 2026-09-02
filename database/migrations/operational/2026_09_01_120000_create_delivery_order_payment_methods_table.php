<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_order_payment_methods', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('delivery_order_id')->constrained()->cascadeOnDelete();
            $table->string('method');
            $table->decimal('amount', 10, 2);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_order_payment_methods');
    }
};
