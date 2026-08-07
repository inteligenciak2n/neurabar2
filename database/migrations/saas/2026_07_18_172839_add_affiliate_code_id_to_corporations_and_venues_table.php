<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->table('corporations', function (Blueprint $table): void {
            $table->foreignUuid('affiliate_code_id')->nullable()->after('owner_id')->constrained('affiliate_codes')->nullOnDelete();
        });

        Schema::connection('saas')->table('venues', function (Blueprint $table): void {
            $table->foreignUuid('affiliate_code_id')->nullable()->after('corporation_id')->constrained('affiliate_codes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->table('corporations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('affiliate_code_id');
        });

        Schema::connection('saas')->table('venues', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('affiliate_code_id');
        });
    }
};
