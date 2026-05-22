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
        Schema::create('order_item_modifiers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('modifier_option_id')->constrained()->cascadeOnDelete();
            $table->decimal('extra_price_snapshot', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_modifiers');
    }
};
