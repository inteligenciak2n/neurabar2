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
        Schema::table('venue_settings', function (Blueprint $table): void {
            $table->json('accepted_delivery_payment_methods')->nullable()->after('table_count');
            $table->boolean('delivery_enabled')->default(true)->after('accepted_delivery_payment_methods');
            $table->boolean('pickup_enabled')->default(true)->after('delivery_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venue_settings', function (Blueprint $table): void {
            $table->dropColumn(['accepted_delivery_payment_methods', 'delivery_enabled', 'pickup_enabled']);
        });
    }
};
