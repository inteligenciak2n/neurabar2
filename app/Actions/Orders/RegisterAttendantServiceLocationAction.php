<?php

namespace App\Actions\Orders;

use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\Venue;
use App\Support\OperationalConnection;
use App\Support\ServiceLocationName;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterAttendantServiceLocationAction
{
    /**
     * @param  array{attendance_channel_id: string, number: int}  $data
     */
    public function execute(Venue $venue, array $data): ServiceLocation
    {
        $channel = AttendanceChannel::query()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->find($data['attendance_channel_id']);

        if (! $channel || ! $channel->uses_service_location) {
            throw ValidationException::withMessages([
                'attendance_channel_id' => 'This channel does not require a consumption location.',
            ]);
        }

        $number = (int) $data['number'];
        $name = ServiceLocationName::compose((string) $channel->name, $number);

        return DB::connection(OperationalConnection::current())->transaction(function () use ($venue, $channel, $name): ServiceLocation {
            $existing = ServiceLocation::query()
                ->where('venue_id', $venue->id)
                ->get()
                ->first(fn (ServiceLocation $location): bool => mb_strtolower($location->name) === mb_strtolower($name));

            if ($existing) {
                if ((string) $existing->default_attendance_channel_id !== (string) $channel->id) {
                    throw ValidationException::withMessages([
                        'number' => 'This location already exists.',
                    ]);
                }

                if (! $existing->active) {
                    $existing->update(['active' => true]);
                }

                return $existing->refresh();
            }

            return ServiceLocation::create([
                'venue_id' => $venue->id,
                'name' => $name,
                'active' => true,
                'default_attendance_channel_id' => $channel->id,
            ]);
        });
    }
}
