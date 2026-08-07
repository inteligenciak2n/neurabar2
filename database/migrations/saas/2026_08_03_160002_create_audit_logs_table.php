<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    /**
     * Ações sensíveis do backoffice (preço de plano, desconto, status de
     * fatura, usuários de plataforma) não deixavam rastro de quem fez o quê.
     */
    public function up(): void
    {
        Schema::connection('saas')->create('audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('action');
            $table->nullableUuidMorphs('auditable');
            $table->uuid('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index(['actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('audit_logs');
    }
};
