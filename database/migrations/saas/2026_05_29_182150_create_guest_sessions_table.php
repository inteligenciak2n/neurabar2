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
        Schema::create('guest_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('venue_id')->constrained()->cascadeOnDelete();
            // FKs para tabelas operacionais não podem ser declaradas no banco saas (cross-database)
            $table->uuid('service_location_id')->nullable();
            $table->uuid('attendance_channel_id')->nullable();
            $table->uuid('attendance_id')->nullable();
            $table->string('guest_token')->unique();
            $table->string('pin')->nullable();
            $table->boolean('geolocation_verified')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_sessions');
    }
};
