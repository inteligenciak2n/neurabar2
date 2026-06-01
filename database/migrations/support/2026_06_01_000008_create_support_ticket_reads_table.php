<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'support';

    public function up(): void
    {
        Schema::connection('support')->create('support_ticket_reads', function (Blueprint $table) {
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
        Schema::connection('support')->dropIfExists('support_ticket_reads');
    }
};
