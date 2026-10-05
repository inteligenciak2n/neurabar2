<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table): void {
            $table->unsignedInteger('code')->nullable()->after('id');
            $table->unique(['venue_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table): void {
            $table->dropUnique(['venue_id', 'code']);
            $table->dropColumn('code');
        });
    }
};
