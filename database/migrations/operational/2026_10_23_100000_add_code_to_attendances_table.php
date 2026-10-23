<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->string('code', 32)->nullable()->after('customer_identifier');
        });

        // A tab card returns to the pool once its attendance closes, so uniqueness only binds open ones.
        Schema::getConnection()->statement(
            "CREATE UNIQUE INDEX attendances_open_code_unique ON attendances (venue_id, code) WHERE status = 'open' AND code IS NOT NULL"
        );
    }

    public function down(): void
    {
        Schema::getConnection()->statement('DROP INDEX IF EXISTS attendances_open_code_unique');

        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropColumn('code');
        });
    }
};
