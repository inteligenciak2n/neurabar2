<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table): void {
            $table->string('delivery_comment')->nullable()->after('address_reference_point');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table): void {
            $table->dropColumn('delivery_comment');
        });
    }
};
