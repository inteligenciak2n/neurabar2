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
        Schema::create('service_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('venue_id')->index();
            $table->foreignUuid('service_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('attendance_id')->nullable()->constrained()->nullOnDelete();
            // users lives on the saas connection, so no FK constraint across databases.
            $table->uuid('assigned_user_id')->nullable();
            $table->string('type');
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->uuid('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->uuid('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
