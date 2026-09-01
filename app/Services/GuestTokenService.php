<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\GuestSession;
use App\Models\Orders\Attendance;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestTokenService
{
    /**
     * Encode a deterministic, venue-only token (no service_location/channel).
     * Used for fixed public links such as the Delivery/Takeaway ordering page.
     */
    public function encodeVenueOnly(Venue $venue): string
    {
        return rtrim(base64_encode(json_encode(['v' => $venue->id])), '=');
    }

    /**
     * Decode a QR token into its components.
     *
     * @return array{venue: Venue, serviceLocation: ?ServiceLocation, attendanceChannel: ?AttendanceChannel}
     */
    public function decode(string $token): array
    {
        $json = base64_decode(str_pad($token, strlen($token) + (4 - strlen($token) % 4) % 4, '='));

        if ($json === false) {
            abort(404);
        }

        $payload = json_decode($json, true);

        if (! is_array($payload) || empty($payload['v'])) {
            abort(404);
        }

        $venue = Venue::withoutGlobalScopes()->find($payload['v'] ?? null);

        abort_if($venue === null, 404);

        $serviceLocation = isset($payload['l'])
            ? ServiceLocation::withoutGlobalScopes()->find($payload['l'])
            : null;

        $attendanceChannel = isset($payload['c']) && $payload['c']
            ? AttendanceChannel::withoutGlobalScopes()->find($payload['c'])
            : null;

        return compact('venue', 'serviceLocation', 'attendanceChannel');
    }

    /**
     * Resolve an existing active GuestSession from cookie or return null.
     */
    public function resolveSession(Request $request, Venue $venue): ?GuestSession
    {
        $guestToken = $request->cookie('guest_token');

        if (! $guestToken) {
            return null;
        }

        return GuestSession::withoutGlobalScopes()
            ->where('guest_token', $guestToken)
            ->where('venue_id', $venue->id)
            ->active()
            ->first();
    }

    /**
     * Create a new GuestSession with a unique token (PIN set separately).
     */
    public function createSession(
        Venue $venue,
        ?ServiceLocation $serviceLocation,
        ?AttendanceChannel $attendanceChannel,
        string $pin
    ): GuestSession {
        return GuestSession::withoutGlobalScopes()->create([
            'venue_id' => $venue->id,
            'service_location_id' => $serviceLocation?->id,
            'attendance_channel_id' => $attendanceChannel?->id,
            'guest_token' => (string) Str::uuid(),
            'pin' => bcrypt($pin),
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Create an Attendance linked to the session if one doesn't exist yet.
     */
    public function createAttendanceIfNeeded(GuestSession $session): Attendance
    {
        if ($session->attendance_id) {
            return $session->attendance()->withoutGlobalScopes()->first();
        }

        $attendance = Attendance::withoutGlobalScopes()->create([
            'venue_id' => $session->venue_id,
            'service_location_id' => $session->service_location_id,
            'attendance_channel_id' => $session->attendance_channel_id,
            'status' => AttendanceStatus::Open,
        ]);

        $session->update(['attendance_id' => $attendance->id]);

        return $attendance;
    }
}
