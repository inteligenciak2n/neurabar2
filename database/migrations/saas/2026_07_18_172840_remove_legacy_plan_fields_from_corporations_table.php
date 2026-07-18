<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->table('corporations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('plan_catalog_id');
            $table->dropColumn([
                'plan_name',
                'subscription_value',
                'plan_start_date',
                'plan_end_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->table('corporations', function (Blueprint $table): void {
            $table->foreignUuid('plan_catalog_id')->nullable()->constrained('plan_catalogs')->nullOnDelete();
            $table->string('plan_name')->nullable();
            $table->decimal('subscription_value', 10, 2)->nullable();
            $table->date('plan_start_date')->nullable();
            $table->date('plan_end_date')->nullable();
        });
    }
};
