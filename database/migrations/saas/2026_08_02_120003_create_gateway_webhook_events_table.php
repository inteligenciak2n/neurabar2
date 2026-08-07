<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('gateway_webhook_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('gateway');
            $table->string('event_id');
            $table->string('event_type');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('gateway_webhook_events');
    }
};
