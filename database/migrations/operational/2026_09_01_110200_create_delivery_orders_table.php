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
        Schema::create('delivery_orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('venue_id')->index();
            $table->foreignUuid('attendance_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('fulfillment_type');

            // Referenciam registros do banco saas (Customer/CustomerAddress) — sem FK, bancos diferentes.
            $table->uuid('customer_id')->nullable();
            $table->uuid('customer_address_id')->nullable();

            $table->foreignUuid('delivery_fee_zone_id')->nullable()->constrained('delivery_fee_zones')->nullOnDelete();
            $table->decimal('delivery_fee', 10, 2)->default(0);

            // Snapshot dos dados do cliente/endereço no momento do pedido.
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('address_street')->nullable();
            $table->string('address_number')->nullable();
            $table->string('address_complement')->nullable();
            $table->string('address_neighborhood')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state')->nullable();
            $table->string('address_zip_code')->nullable();
            $table->string('address_reference_point')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
