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
            // Indica se o tenant usa banco dedicado ou compartilhado
            $table->boolean('is_dedicated')->default(false)->after('self_connection');
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->table('corporations', function (Blueprint $table): void {
            $table->dropColumn('is_dedicated');
        });
    }
};
