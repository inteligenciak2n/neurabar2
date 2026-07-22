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
            $table->timestamp('terms_accepted_at')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->table('corporation_subscriptions', function (Blueprint $table): void {
            $table->dropColumn('terms_accepted_at');
        });
    }
};
