<?php

namespace Tests\Feature\Orders;

use App\Enums\UserRole;
use App\Models\Orders\Attendance;
use App\Models\Payment\Payment;
use App\Models\Tenant\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendant_can_open_attendance(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $this->post(route('attendances.store'), [
            'channel' => 'counter',
        ])->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('attendances', ['venue_id' => $venue->id, 'channel' => 'counter', 'status' => 'open']);
    }

    public function test_opening_table_attendance_without_identifier_fails_when_require_table(): void
    {
        $venue = Venue::factory()->create(['require_table' => true]);
        $this->loginAs(UserRole::Attendant, $venue);

        $this->post(route('attendances.store'), [
            'channel' => 'table',
        ])->assertSessionHasErrors('customer_identifier');
    }

    public function test_opening_table_attendance_with_identifier_when_require_table(): void
    {
        $venue = Venue::factory()->create(['require_table' => true]);
        $this->loginAs(UserRole::Attendant, $venue);

        $this->post(route('attendances.store'), [
            'channel' => 'table',
            'customer_identifier' => 'Table 7',
        ])->assertRedirect(route('attendances.index'));
    }

    public function test_close_without_payment_returns_422(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);

        $this->post(route('attendances.close', $attendance->id))
            ->assertSessionHasErrors('payment');
    }

    public function test_close_with_payment_succeeds(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        Payment::factory()->create(['attendance_id' => $attendance->id]);

        $this->post(route('attendances.close', $attendance->id))
            ->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('attendances', ['id' => $attendance->id, 'status' => 'closed']);
    }

    public function test_index_returns_only_tenant_attendances(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        Attendance::factory()->open()->create(['venue_id' => $venue->id]);

        $otherVenue = Venue::factory()->create();
        Attendance::factory()->open()->create(['venue_id' => $otherVenue->id]);

        $response = $this->get(route('attendances.index'))->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Attendances/Index')
            ->has('attendances', 1)
        );
    }

    public function test_other_tenant_attendance_is_not_accessible(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Attendant, $venue);

        $otherVenue = Venue::factory()->create();
        $otherAttendance = Attendance::factory()->open()->create(['venue_id' => $otherVenue->id]);

        $this->get(route('attendances.show', $otherAttendance->id))->assertNotFound();
    }
}
