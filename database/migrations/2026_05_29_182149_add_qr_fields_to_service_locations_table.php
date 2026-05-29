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
        Schema::table('service_locations', function (Blueprint $table) {
            $table->foreignUuid('default_attendance_channel_id')
                ->nullable()
                ->after('type')
                ->constrained('attendance_channels')
                ->nullOnDelete();
            $table->string('qr_token')->unique()->nullable()->after('default_attendance_channel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_locations', function (Blueprint $table): void {
            $table->dropForeign(['default_attendance_channel_id']);
            $table->dropColumn(['default_attendance_channel_id', 'qr_token']);
        });
    }
};
