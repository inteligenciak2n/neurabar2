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
        Schema::create('corporation_discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('corporation_id')->constrained('corporations')->cascadeOnDelete();
            $table->string('type'); // percentage, fixed
            $table->decimal('value', 12, 2);
            $table->string('description')->nullable();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->integer('max_months')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['corporation_id', 'is_active', 'valid_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporation_discounts');
    }
};
