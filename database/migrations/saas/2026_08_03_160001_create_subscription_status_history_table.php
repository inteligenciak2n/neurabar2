<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    /**
     * Sem histórico não havia como responder "por que este cliente foi
     * suspenso em tal dia" — o status atual sobrescrevia a própria evidência.
     */
    public function up(): void
    {
        Schema::connection('saas')->create('subscription_status_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('subscription');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('reason')->nullable();
            $table->uuid('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamps();

            $table->index(['subscription_type', 'subscription_id', 'created_at'], 'subscription_status_history_subject_index');
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('subscription_status_history');
    }
};
