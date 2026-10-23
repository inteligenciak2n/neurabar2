<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\StoreServiceLocationRangeRequest;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\Venue;
use App\Support\OperationalConnection;
use App\Support\ServiceLocationName;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateServiceLocationRangeAction
{
    /** @return array{created: int, skipped: int} */
    public function execute(Venue $venue, StoreServiceLocationRangeRequest $request): array
    {
        $from = (int) $request->validated('from');
        $to = (int) $request->validated('to');
        $channel = AttendanceChannel::query()->findOrFail($request->validated('default_attendance_channel_id'));
        $prefix = $channel->name;

        return DB::connection(OperationalConnection::current())->transaction(function () use ($venue, $from, $to, $prefix, $channel): array {
            $existing = ServiceLocation::query()
                ->where('venue_id', $venue->id)
                ->pluck('name')
                ->map(fn (string $name): string => mb_strtolower($name))
                ->all();

            $created = 0;
            $skipped = 0;

            for ($number = $from; $number <= $to; $number++) {
                $name = ServiceLocationName::compose($prefix, $number);
                $key = mb_strtolower($name);

                if (in_array($key, $existing, true)) {
                    $skipped++;

                    continue;
                }

                ServiceLocation::create([
                    'venue_id' => $venue->id,
                    'name' => $name,
                    'active' => true,
                    'default_attendance_channel_id' => $channel->id,
                ]);

                $existing[] = $key;
                $created++;
            }

            if ($created === 0) {
                throw ValidationException::withMessages([
                    'from' => 'Every location in this range already exists.',
                ]);
            }

            return ['created' => $created, 'skipped' => $skipped];
        });
    }
}
