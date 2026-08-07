<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('venue_modules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('venue_id')->constrained()->cascadeOnDelete();
            $table->string('module_code');
            $table->string('status')->default('active');
            $table->integer('quantity')->default(1);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->unique(['venue_id', 'module_code']);
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('venue_modules');
    }
};
