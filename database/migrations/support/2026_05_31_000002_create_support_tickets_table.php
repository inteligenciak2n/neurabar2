<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'support';

    public function up(): void
    {
        Schema::connection('support')->create('support_tickets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('venue_id')->nullable();
            $table->foreignUuid('category_id')->constrained('support_ticket_categories')->nullOnDelete();
            $table->uuid('assigned_to')->nullable();
            $table->string('subject');
            $table->string('status')->default('open');
            $table->string('priority')->default('medium');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('venue_id');
            $table->index('assigned_to');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('support')->dropIfExists('support_tickets');
    }
};
