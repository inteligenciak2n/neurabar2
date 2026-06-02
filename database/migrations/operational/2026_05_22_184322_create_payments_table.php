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
        Schema::create('payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('attendance_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('items_total', 10, 2);
            $table->decimal('cover_charge_total', 10, 2);
            $table->decimal('service_fee_total', 10, 2);
            $table->decimal('grand_total', 10, 2);
            $table->integer('party_size')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
