<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->table('corporation_subscriptions', function (Blueprint $table): void {
            $table->string('gateway')->nullable()->after('currency');
            $table->string('gateway_customer_id')->nullable()->after('gateway');
            $table->string('gateway_subscription_id')->nullable()->unique()->after('gateway_customer_id');
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->table('corporation_subscriptions', function (Blueprint $table): void {
            $table->dropColumn(['gateway', 'gateway_customer_id', 'gateway_subscription_id']);
        });
    }
};
