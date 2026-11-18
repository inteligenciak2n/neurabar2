<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_customer_addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('venue_id')->index();
            $table->foreignUuid('venue_customer_id')->constrained('venue_customers')->cascadeOnDelete();
            $table->string('zip_code', 8);
            $table->string('street');
            $table->string('number', 30);
            $table->string('complement')->nullable();
            $table->foreignUuid('delivery_fee_zone_id')->nullable()->constrained('delivery_fee_zones')->nullOnDelete();
            $table->string('neighborhood');
            $table->string('city');
            $table->string('state', 2);
            $table->string('reference_point', 500)->nullable();
            $table->string('delivery_phone', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['venue_customer_id', 'zip_code', 'number']);
        });

        Schema::create('attendance_delivery_addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('attendance_id')->unique()->constrained('attendances')->cascadeOnDelete();
            $table->foreignUuid('venue_customer_address_id')->nullable()->constrained('venue_customer_addresses')->nullOnDelete();
            $table->foreignUuid('delivery_fee_zone_id')->nullable()->constrained('delivery_fee_zones')->nullOnDelete();
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->string('zip_code', 8);
            $table->string('street');
            $table->string('number', 30);
            $table->string('complement')->nullable();
            $table->string('neighborhood');
            $table->string('city');
            $table->string('state', 2);
            $table->string('reference_point', 500)->nullable();
            $table->string('delivery_phone', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_delivery_addresses');
        Schema::dropIfExists('venue_customer_addresses');
    }
};
