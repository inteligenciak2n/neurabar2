<?php

namespace Tests\Feature\Orders;

use App\Enums\UserRole;
use App\Models\Orders\Attendance;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class AttendanceServiceLocationUpdateTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_attendant_can_change_the_service_location_of_an_open_attendance(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $newLocation = ServiceLocation::factory()->create(['venue_id' => $venue->id]);

        $this->put(route('attendances.update', $attendance->id), [
            'service_location_id' => $newLocation->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'service_location_id' => $newLocation->id,
        ]);
    }

    public function test_rejects_a_service_location_from_another_venue(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $otherVenueLocation = ServiceLocation::factory()->create(['venue_id' => Venue::factory()->create()->id]);

        $this->put(route('attendances.update', $attendance->id), [
            'service_location_id' => $otherVenueLocation->id,
        ])->assertSessionHasErrors('service_location_id');

        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
            'service_location_id' => $otherVenueLocation->id,
        ]);
    }
}
