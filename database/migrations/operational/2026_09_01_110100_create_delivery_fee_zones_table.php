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
        Schema::create('delivery_fee_zones', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('venue_id')->index();
            $table->string('label')->nullable();
            $table->unsignedInteger('zip_code_start');
            $table->unsignedInteger('zip_code_end');
            $table->decimal('fee', 10, 2);
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['venue_id', 'zip_code_start', 'zip_code_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_fee_zones');
    }
};
