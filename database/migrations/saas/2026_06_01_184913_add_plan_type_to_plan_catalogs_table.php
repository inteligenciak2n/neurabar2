<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->table('plan_catalogs', function (Blueprint $table): void {
            // Define se planos deste tipo usam banco compartilhado ou dedicado.
            // Usado no futuro para automatizar TenantConnectionResolver::resolve().
            $table->enum('plan_type', ['shared', 'dedicated'])->default('shared')->after('active');
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->table('plan_catalogs', function (Blueprint $table): void {
            $table->dropColumn('plan_type');
        });
    }
};
