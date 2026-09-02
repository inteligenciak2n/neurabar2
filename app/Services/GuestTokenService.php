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
     *
     * Signed with an HMAC so a leaked/guessed venue id alone can't forge a
     * valid token; see decode() for the (backward-compatible) verification.
     */
    public function encodeVenueOnly(Venue $venue): string
    {
        $payload = ['v' => $venue->id];
        $payload['s'] = $this->sign($payload);

        return rtrim(base64_encode(json_encode($payload)), '=');
    }

    /**
     * Decode a QR token into its components.
     *
     * Tokens carrying a signature ('s') have it verified against the rest of
     * the payload; unsigned tokens (e.g. service_location QR codes already
     * printed before this signature was introduced) are still accepted to
     * avoid invalidating physical QR codes in production.
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

        if (isset($payload['s'])) {
            $signature = $payload['s'];
            unset($payload['s']);

            abort_unless(hash_equals($this->sign($payload), $signature), 404);
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

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sign(array $payload): string
    {
        ksort($payload);

        return hash_hmac('sha256', json_encode($payload), config('app.key'));
    }
}
