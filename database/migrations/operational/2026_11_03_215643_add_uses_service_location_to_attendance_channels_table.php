<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_channels', function (Blueprint $table): void {
            $table->boolean('uses_service_location')->default(true)->after('requires_tab');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_channels', function (Blueprint $table): void {
            $table->dropColumn('uses_service_location');
        });
    }
};
