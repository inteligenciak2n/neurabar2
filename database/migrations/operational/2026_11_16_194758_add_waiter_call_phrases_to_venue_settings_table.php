<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venue_settings', function (Blueprint $table): void {
            $table->json('waiter_call_phrases')->nullable()->after('tabs_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('venue_settings', function (Blueprint $table): void {
            $table->dropColumn('waiter_call_phrases');
        });
    }
};
