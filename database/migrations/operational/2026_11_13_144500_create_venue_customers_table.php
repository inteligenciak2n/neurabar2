<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_customers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('venue_id')->index();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('address', 500)->nullable();
            $table->string('comment', 500)->nullable();
            $table->timestamps();

            $table->unique(['venue_id', 'phone']);
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->foreignUuid('venue_customer_id')
                ->nullable()
                ->after('customer_identifier')
                ->constrained('venue_customers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('venue_customer_id');
        });

        Schema::dropIfExists('venue_customers');
    }
};
