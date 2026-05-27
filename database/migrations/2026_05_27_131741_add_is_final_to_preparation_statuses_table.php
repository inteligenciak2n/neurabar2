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
        Schema::table('preparation_statuses', function (Blueprint $table): void {
            $table->boolean('is_initial')->default(false)->after('show_to_customer');
            $table->boolean('is_final')->default(false)->after('is_initial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preparation_statuses', function (Blueprint $table): void {
            $table->dropColumn('is_initial');
            $table->dropColumn('is_final');
        });
    }
};
