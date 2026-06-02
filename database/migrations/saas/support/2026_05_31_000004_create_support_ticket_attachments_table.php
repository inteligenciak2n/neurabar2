<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('support_ticket_attachments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('message_id')->constrained('support_ticket_messages')->cascadeOnDelete();
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('support_ticket_attachments');
    }
};
