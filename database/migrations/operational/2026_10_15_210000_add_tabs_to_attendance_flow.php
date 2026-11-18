<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_channels', function (Blueprint $table): void {
            $table->boolean('requires_tab')->default(false)->after('requires_customer_identifier');
        });

        Schema::create('tabs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('venue_id')->index();
            $table->foreignUuid('attendance_channel_id')->constrained('attendance_channels')->cascadeOnDelete();
            $table->string('code', 20);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['venue_id', 'code']);
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->foreignUuid('tab_id')->nullable()->after('attendance_channel_id')->constrained('tabs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tab_id');
        });

        Schema::dropIfExists('tabs');

        Schema::table('attendance_channels', function (Blueprint $table): void {
            $table->dropColumn('requires_tab');
        });
    }
};
