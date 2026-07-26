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
        Schema::connection('saas')->table('corporations', function (Blueprint $table): void {
            $table->json('billing_address_json')->nullable()->after('tax_id');
            $table->string('billing_tax_regime')->nullable()->after('billing_address_json');
            $table->string('billing_state_registration')->nullable()->after('billing_tax_regime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('saas')->table('corporations', function (Blueprint $table): void {
            $table->dropColumn(['billing_address_json', 'billing_tax_regime', 'billing_state_registration']);
        });
    }
};
