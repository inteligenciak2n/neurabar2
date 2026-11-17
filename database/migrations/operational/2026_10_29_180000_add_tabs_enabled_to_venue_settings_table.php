<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venue_settings', function (Blueprint $table): void {
            $table->boolean('tabs_enabled')->default(true)->after('table_count');
        });
    }

    public function down(): void
    {
        Schema::table('venue_settings', function (Blueprint $table): void {
            $table->dropColumn('tabs_enabled');
        });
    }
};
