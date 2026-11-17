<?php

use App\Enums\KitchenStationCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchen_stations', function (Blueprint $table): void {
            $table->string('code')->nullable()->after('name');
            $table->unique(['venue_id', 'code']);
        });

        $code = KitchenStationCode::WaiterCaller;
        $now = now();

        $venueIds = DB::table('venue_settings')->pluck('venue_id')
            ->merge(DB::table('kitchen_stations')->pluck('venue_id'))
            ->unique();

        foreach ($venueIds as $venueId) {
            $exists = DB::table('kitchen_stations')
                ->where('venue_id', $venueId)
                ->where('code', $code->value)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('kitchen_stations')->insert([
                'id' => (string) Str::uuid(),
                'venue_id' => $venueId,
                'name' => $code->defaultName(),
                'code' => $code->value,
                'sort_order' => 0,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('kitchen_stations', function (Blueprint $table): void {
            $table->dropUnique(['venue_id', 'code']);
            $table->dropColumn('code');
        });
    }
};
