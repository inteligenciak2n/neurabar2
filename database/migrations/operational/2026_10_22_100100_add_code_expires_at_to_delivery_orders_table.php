<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table): void {
            $table->timestamp('code_expires_at')->nullable()->after('code');
            $table->index('code_expires_at');
        });

        // Codes issued before this column existed start their TTL now.
        DB::table('delivery_orders')
            ->whereNotNull('code')
            ->update(['code_expires_at' => now()->addHours((int) config('platform.delivery_code_ttl_hours'))]);
    }

    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table): void {
            $table->dropIndex(['code_expires_at']);
            $table->dropColumn('code_expires_at');
        });
    }
};
