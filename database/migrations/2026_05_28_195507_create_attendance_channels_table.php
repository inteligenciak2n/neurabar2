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
        Schema::create('attendance_channels', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('venue_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_trackable')->default(true);
            $table->boolean('requires_customer_identifier')->default(false);
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->foreign('attendance_channel_id')
                ->references('id')
                ->on('attendance_channels')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropForeign(['attendance_channel_id']);
        });

        Schema::dropIfExists('attendance_channels');
    }
};
