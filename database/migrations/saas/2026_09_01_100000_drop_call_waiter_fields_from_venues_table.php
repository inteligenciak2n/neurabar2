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
        Schema::table('venues', function (Blueprint $table): void {
            $table->dropColumn(['call_waiter_header_url', 'call_waiter_passphrase', 'call_waiter_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table): void {
            $table->string('call_waiter_header_url')->nullable();
            $table->string('call_waiter_passphrase')->nullable();
            $table->string('call_waiter_slug')->unique()->nullable();
        });
    }
};
