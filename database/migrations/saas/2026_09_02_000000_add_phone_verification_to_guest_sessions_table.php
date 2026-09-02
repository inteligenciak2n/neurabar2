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
        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->string('verified_phone')->nullable()->after('pin');
            $table->timestamp('phone_verified_at')->nullable()->after('verified_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->dropColumn(['verified_phone', 'phone_verified_at']);
        });
    }
};
