<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('support_tutorials', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained('support_tutorial_categories')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary')->nullable();
            $table->text('body');
            $table->string('featured_image')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('support_tutorials');
    }
};
