<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('venue_plan_change_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('pending_venue_id')->nullable()->unique()->constrained('venues')->cascadeOnDelete();
            $table->foreignUuid('requested_plan_catalog_id')->constrained('plan_catalogs')->restrictOnDelete();
            $table->foreignUuid('requested_plan_catalog_version_id')->constrained('plan_catalog_versions')->restrictOnDelete();
            $table->foreignUuid('approved_assignment_id')->nullable()->constrained('venue_plan_assignments')->nullOnDelete();
            $table->foreignUuid('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->date('effective_on');
            $table->text('reason')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['venue_id', 'status']);
            $table->index(['status', 'effective_on']);
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('venue_plan_change_requests');
    }
};
