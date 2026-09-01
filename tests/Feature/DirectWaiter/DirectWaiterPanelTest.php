<?php

namespace Tests\Feature\DirectWaiter;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use App\Enums\UserRole;
use App\Models\Orders\ServiceRequest;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class DirectWaiterPanelTest extends TestCase
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

    public function test_blocks_access_when_direct_waiter_module_is_inactive(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $this->get(route('direct-waiter.index'))->assertForbidden();
    }

    public function test_index_only_lists_message_requests_for_the_current_venue(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);
        $this->activateDirectWaiter($venue);

        $mine = ServiceRequest::factory()->create(['venue_id' => $venue->id, 'type' => ServiceRequestType::Message]);
        ServiceRequest::factory()->callToOrder()->create(['venue_id' => $venue->id]);
        ServiceRequest::factory()->create(['venue_id' => Venue::factory()->create()->id, 'type' => ServiceRequestType::Message]);

        $response = $this->get(route('direct-waiter.index'))->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('DirectWaiter/Index')
            ->has('requests', 1)
            ->where('requests.0.id', $mine->id));
    }

    public function test_acknowledge_and_resolve_update_status_and_actor(): void
    {
        $venue = Venue::factory()->create();
        $user = $this->loginAs(UserRole::Owner, $venue);
        $this->activateDirectWaiter($venue);

        $request = ServiceRequest::factory()->create(['venue_id' => $venue->id]);

        $this->put(route('service-requests.acknowledge', $request->id))->assertRedirect();

        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'status' => ServiceRequestStatus::Acknowledged->value,
            'acknowledged_by' => $user->id,
        ]);

        $this->put(route('service-requests.resolve', $request->id))->assertRedirect();

        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'status' => ServiceRequestStatus::Resolved->value,
            'resolved_by' => $user->id,
        ]);
    }

    public function test_cannot_acknowledge_a_service_request_from_another_venue(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);
        $this->activateDirectWaiter($venue);

        $otherVenue = Venue::factory()->create();
        $request = ServiceRequest::factory()->create(['venue_id' => $otherVenue->id]);

        $this->put(route('service-requests.acknowledge', $request->id))->assertNotFound();
    }
}
