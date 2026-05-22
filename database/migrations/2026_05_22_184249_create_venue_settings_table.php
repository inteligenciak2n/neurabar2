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
        Schema::create('venue_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('venue_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('cover_charge', 10, 2)->default(10.00);
            $table->decimal('service_fee_percent', 5, 2)->default(10.00);
            $table->integer('table_count')->default(30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_settings');
    }
};
