<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('gateway_customers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('owner');
            $table->string('gateway');
            $table->string('customer_id');
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id', 'gateway']);
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('gateway_customers');
    }
};
