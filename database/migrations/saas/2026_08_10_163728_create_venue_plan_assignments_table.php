<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('venue_plan_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('plan_catalog_id')->constrained('plan_catalogs')->restrictOnDelete();
            $table->foreignUuid('plan_catalog_version_id')->constrained('plan_catalog_versions')->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('source')->default('backoffice');
            $table->timestamps();

            $table->index(['venue_id', 'starts_on', 'ends_on'], 'venue_plan_assignment_period_index');
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('venue_plan_assignments');
    }
};
