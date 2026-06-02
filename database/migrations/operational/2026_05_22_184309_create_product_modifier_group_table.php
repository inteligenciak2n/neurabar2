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
        Schema::create('product_modifier_group', function (Blueprint $table): void {
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('modifier_group_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'modifier_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_modifier_group');
    }
};
