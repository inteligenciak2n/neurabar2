<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\StoreServiceLocationRangeRequest;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\Venue;
use App\Support\OperationalConnection;
use App\Support\ServiceLocationName;
use Illuminate\Support\Facades\DB;

class CreateServiceLocationRangeAction
{
    /**
     * @return array{
     *     created: int,
     *     skipped: int,
     *     created_names: list<string>,
     *     skipped_names: list<string>
     * }
     */
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

            $createdNames = [];
            $skippedNames = [];

            for ($number = $from; $number <= $to; $number++) {
                $name = ServiceLocationName::compose($prefix, $number);
                $key = mb_strtolower($name);

                if (in_array($key, $existing, true)) {
                    $skippedNames[] = $name;

                    continue;
                }

                ServiceLocation::create([
                    'venue_id' => $venue->id,
                    'name' => $name,
                    'active' => true,
                    'default_attendance_channel_id' => $channel->id,
                ]);

                $existing[] = $key;
                $createdNames[] = $name;
            }

            return [
                'created' => count($createdNames),
                'skipped' => count($skippedNames),
                'created_names' => $createdNames,
                'skipped_names' => $skippedNames,
            ];
        });
    }
}
