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
            $table->foreign('current_venue_id')->references('id')->on('venues')->nullOnDelete();
        });

        Schema::table('corporations', function (Blueprint $table): void {
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('corporations', function (Blueprint $table): void {
            $table->dropForeign(['owner_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['current_venue_id']);
        });
    }
};
