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
        Schema::create('attendances', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('venue_id')->index();
            $table->foreignUuid('service_location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_identifier')->nullable();
            $table->uuid('attendance_channel_id')->nullable();
            $table->string('status')->default('open');
            $table->integer('party_size')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
