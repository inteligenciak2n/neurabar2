<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->table('venue_usage_records', function (Blueprint $table): void {
            $table->foreignUuid('venue_plan_assignment_id')->nullable()->after('tier_id')
                ->constrained('venue_plan_assignments')->restrictOnDelete();
            $table->foreignUuid('plan_catalog_version_id')->nullable()->after('venue_plan_assignment_id')
                ->constrained('plan_catalog_versions')->restrictOnDelete();
            $table->foreignUuid('plan_module_usage_tier_id')->nullable()->after('plan_catalog_version_id')
                ->constrained('plan_module_usage_tiers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->table('venue_usage_records', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('plan_module_usage_tier_id');
            $table->dropConstrainedForeignId('plan_catalog_version_id');
            $table->dropConstrainedForeignId('venue_plan_assignment_id');
        });
    }
};
