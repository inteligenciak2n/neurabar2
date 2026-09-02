<?php

namespace Tests\Feature\Orders;

use App\Actions\Orders\CloseAttendanceAction;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\UserRole;
use App\Models\Orders\Attendance;
use App\Models\Orders\ServiceRequest;
use App\Models\Payment\Payment;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ClaimAttendanceTest extends TestCase
{
    use RefreshAllDatabases;

    private function activateDirectWaiter(Venue $venue): void
    {
        CorporationModule::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'module_code' => ModuleCode::DirectWaiter->value,
            'status' => ModuleStatus::Active,
        ]);

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::DirectWaiter->value,
            'status' => ModuleStatus::Active,
        ]);
    }

    public function test_attendant_can_claim_an_open_attendance_and_backfills_open_requests(): void
    {
        $venue = Venue::factory()->create();
        $user = $this->loginAs(UserRole::Owner, $venue);
        $this->activateDirectWaiter($venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $request = ServiceRequest::factory()->create([
            'venue_id' => $venue->id,
            'attendance_id' => $attendance->id,
        ]);

        $this->put(route('service-requests.assign', $request->id))->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'claimed_by_user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'assigned_user_id' => $user->id,
        ]);
    }

    public function test_cannot_claim_an_attendance_already_claimed_by_another_user(): void
    {
        $venue = Venue::factory()->create();
        $owner = $this->loginAs(UserRole::Owner, $venue);
        $this->activateDirectWaiter($venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $request = ServiceRequest::factory()->create([
            'venue_id' => $venue->id,
            'attendance_id' => $attendance->id,
        ]);

        $this->put(route('service-requests.assign', $request->id))->assertRedirect();

        $otherUser = User::factory()->create(['current_venue_id' => $venue->id]);
        $venue->users()->attach($otherUser->id, ['role' => UserRole::Attendant->value]);
        $this->actingAs($otherUser);

        $this->put(route('service-requests.assign', $request->id))->assertSessionHasErrors('attendance');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'claimed_by_user_id' => $owner->id,
        ]);
    }

    public function test_cannot_claim_a_closed_attendance(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);
        $this->activateDirectWaiter($venue);

        $attendance = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        $request = ServiceRequest::factory()->create([
            'venue_id' => $venue->id,
            'attendance_id' => $attendance->id,
        ]);

        $this->put(route('service-requests.assign', $request->id))->assertSessionHasErrors('attendance');
    }

    public function test_assign_returns_422_when_service_request_has_no_attendance(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);
        $this->activateDirectWaiter($venue);

        $request = ServiceRequest::factory()->create(['venue_id' => $venue->id, 'attendance_id' => null]);

        $this->put(route('service-requests.assign', $request->id))->assertStatus(422);
    }

    public function test_dashboard_index_hides_requests_assigned_to_other_users(): void
    {
        $venue = Venue::factory()->create();
        $user = $this->loginAs(UserRole::Owner, $venue);
        $this->activateDirectWaiter($venue);

        $unassigned = ServiceRequest::factory()->create(['venue_id' => $venue->id]);
        $mine = ServiceRequest::factory()->create(['venue_id' => $venue->id, 'assigned_user_id' => $user->id]);
        ServiceRequest::factory()->create(['venue_id' => $venue->id, 'assigned_user_id' => (string) Str::uuid()]);

        $response = $this->get(route('direct-waiter.index'))->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('DirectWaiter/Index')
            ->has('requests', 2)
            ->etc());

        $ids = collect($response->viewData('page')['props']['requests'])->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$mine->id, $unassigned->id], $ids);
    }

    public function test_claiming_user_can_release_the_attendance_and_requests_are_unassigned(): void
    {
        $venue = Venue::factory()->create();
        $user = $this->loginAs(UserRole::Owner, $venue);
        $this->activateDirectWaiter($venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $request = ServiceRequest::factory()->create([
            'venue_id' => $venue->id,
            'attendance_id' => $attendance->id,
        ]);

        $this->put(route('service-requests.assign', $request->id))->assertRedirect();
        $this->put(route('service-requests.release', $request->id))->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'claimed_by_user_id' => null,
        ]);

        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'assigned_user_id' => null,
        ]);
    }

    public function test_only_the_claiming_user_can_release_the_attendance(): void
    {
        $venue = Venue::factory()->create();
        $user = $this->loginAs(UserRole::Owner, $venue);
        $this->activateDirectWaiter($venue);

        $attendance = Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        $request = ServiceRequest::factory()->create([
            'venue_id' => $venue->id,
            'attendance_id' => $attendance->id,
        ]);

        $this->put(route('service-requests.assign', $request->id))->assertRedirect();

        $otherUser = User::factory()->create(['current_venue_id' => $venue->id]);
        $venue->users()->attach($otherUser->id, ['role' => UserRole::Attendant->value]);
        $this->actingAs($otherUser);

        $this->put(route('service-requests.release', $request->id))->assertSessionHasErrors('attendance');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'claimed_by_user_id' => $user->id,
        ]);
    }

    public function test_closing_an_attendance_resets_the_claim(): void
    {
        $venue = Venue::factory()->create();
        $user = $this->loginAs(UserRole::Owner, $venue);

        $attendance = Attendance::factory()->open()->create([
            'venue_id' => $venue->id,
            'claimed_by_user_id' => $user->id,
        ]);
        Payment::factory()->create(['attendance_id' => $attendance->id]);

        (new CloseAttendanceAction)->execute($attendance);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'closed',
            'claimed_by_user_id' => null,
        ]);
    }
}
