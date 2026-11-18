<?php

use App\Support\TabCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tab_series', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('venue_id')->index();
            $table->string('prefix', 10);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['venue_id', 'prefix']);
        });

        Schema::table('tabs', function (Blueprint $table): void {
            $table->foreignUuid('tab_series_id')->nullable()->after('venue_id')->constrained('tab_series')->cascadeOnDelete();
        });

        $tabs = DB::table('tabs')->orderBy('code')->get();
        $seriesByKey = [];

        foreach ($tabs as $tab) {
            $prefix = TabCode::prefixFromCode((string) $tab->code);
            $key = $tab->venue_id.'|'.$prefix;

            if (! isset($seriesByKey[$key])) {
                $seriesId = (string) Str::uuid();
                $now = now();

                DB::table('tab_series')->insert([
                    'id' => $seriesId,
                    'venue_id' => $tab->venue_id,
                    'prefix' => $prefix,
                    'notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $seriesByKey[$key] = $seriesId;
            }

            DB::table('tabs')->where('id', $tab->id)->update([
                'tab_series_id' => $seriesByKey[$key],
            ]);
        }

        DB::statement('ALTER TABLE tabs ALTER COLUMN tab_series_id SET NOT NULL');

        Schema::table('tabs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('attendance_channel_id');
        });
    }

    public function down(): void
    {
        Schema::table('tabs', function (Blueprint $table): void {
            $table->foreignUuid('attendance_channel_id')->nullable()->constrained('attendance_channels')->cascadeOnDelete();
        });

        Schema::table('tabs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tab_series_id');
        });

        Schema::dropIfExists('tab_series');
    }
};
