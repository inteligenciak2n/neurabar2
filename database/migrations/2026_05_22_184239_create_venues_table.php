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
        Schema::create('venues', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('corporation_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('tax_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp_agent')->nullable();
            $table->string('street')->nullable();
            $table->string('number')->nullable();
            $table->string('complement')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('timezone')->default('America/Sao_Paulo');
            $table->boolean('active')->default(true);
            $table->boolean('require_table')->default(false);
            $table->boolean('require_tab')->default(false);
            $table->boolean('require_location')->default(false);
            $table->string('call_waiter_header_url')->nullable();
            $table->string('call_waiter_passphrase')->nullable();
            $table->string('call_waiter_slug')->unique()->nullable();
            $table->string('evolution_api_url')->nullable();
            $table->string('evolution_api_key')->nullable();
            $table->string('evolution_api_instance')->nullable();
            $table->string('logo_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
