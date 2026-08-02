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
        Schema::connection('saas')->create('user_payment_methods', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('gateway')->default('fake');
            $table->string('gateway_token');
            $table->string('brand')->nullable();
            $table->string('last4', 4)->nullable();
            $table->string('holder_name');
            $table->string('holder_document')->nullable();
            $table->tinyInteger('expiration_month')->nullable();
            $table->tinyInteger('expiration_year')->nullable();
            $table->boolean('is_default')->default(false);
            $table->json('billing_address_json')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('user_payment_methods');
    }
};
