<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->table('users', function (Blueprint $table): void {
            $table->string('preferred_phone_hand', 16)->default('right')->after('pin');
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->table('users', function (Blueprint $table): void {
            $table->dropColumn('preferred_phone_hand');
        });
    }
};
