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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->nullable()->after('email');
            $table->uuid('venue_id')->nullable()->after('role');
            $table->uuid('corporation_id')->nullable()->after('venue_id');
            $table->string('pin')->nullable()->after('corporation_id');
            $table->boolean('active')->default(true)->after('pin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'venue_id', 'corporation_id', 'pin', 'active']);
        });
    }
};
