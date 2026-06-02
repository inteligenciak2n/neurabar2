<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('support_ticket_reads', function (Blueprint $table) {
            $table->uuid('ticket_id');
            $table->uuid('reader_id');
            $table->string('reader_type'); // 'user' | 'platform_user'
            $table->timestamp('last_read_at');

            $table->primary(['ticket_id', 'reader_id', 'reader_type']);

            $table->foreign('ticket_id')
                ->references('id')
                ->on('support_tickets')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('support_ticket_reads');
    }
};
