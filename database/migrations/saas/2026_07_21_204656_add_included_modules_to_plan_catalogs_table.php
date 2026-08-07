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
        Schema::table('plan_catalogs', function (Blueprint $table) {
            $table->json('included_modules')->nullable()->after('monthly_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_catalogs', function (Blueprint $table): void {
            $table->dropColumn('included_modules');
        });
    }
};
