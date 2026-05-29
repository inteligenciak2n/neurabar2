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
            $table->foreignUuid('service_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('attendance_channel_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('attendance_id')->nullable()->constrained()->nullOnDelete();
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
