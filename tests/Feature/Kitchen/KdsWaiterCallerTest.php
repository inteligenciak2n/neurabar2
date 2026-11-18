<?php

namespace Tests\Feature\Kitchen;

use App\Actions\Corporation\CreateVenueDefaultsAction;
use App\Actions\Kitchen\EnsureWaiterCallerKitchenStationAction;
use App\Enums\KitchenStationCode;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\ServiceRequestType;
use App\Enums\UserRole;
use App\Models\Orders\ServiceRequest;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class KdsWaiterCallerTest extends TestCase
{
    use RefreshAllDatabases;

    private function enableKdsModule(Venue $venue): void
    {
        CorporationModule::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
        ]);
    }

    public function test_kds_always_includes_the_waiter_caller_station(): void
    {
        $venue = Venue::factory()->create();
        $this->enableKdsModule($venue);
        $this->loginAs(UserRole::Attendant, $venue);

        $this->get(route('kitchen.kds'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kitchen/Kds')
                ->has('stations', 1)
                ->where('stations.0.code', KitchenStationCode::WaiterCaller->value)
                ->where('stations.0.name', KitchenStationCode::WaiterCaller->defaultName())
                ->where('stations.0.active', true)
                ->has('waiterCalls'));

        $this->assertDatabaseHas('kitchen_stations', [
            'venue_id' => $venue->id,
            'code' => KitchenStationCode::WaiterCaller->value,
            'active' => true,
        ]);
    }

    public function test_kds_reactivates_the_waiter_caller_station_if_it_was_turned_off(): void
    {
        $venue = Venue::factory()->create();
        $this->enableKdsModule($venue);
        $this->loginAs(UserRole::Attendant, $venue);

        $station = app(EnsureWaiterCallerKitchenStationAction::class)->execute($venue);
        $station->forceFill(['active' => false])->save();

        $this->get(route('kitchen.kds'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stations.0.id', $station->id)
                ->where('stations.0.active', true));

        $this->assertTrue($station->fresh()->active);
    }

    public function test_kds_lists_open_text_messages_on_the_waiter_caller_column(): void
    {
        $venue = Venue::factory()->create();
        $this->enableKdsModule($venue);
        $this->loginAs(UserRole::Attendant, $venue);

        $location = ServiceLocation::factory()->create(['venue_id' => $venue->id, 'name' => 'Mesa 7']);
        $mine = ServiceRequest::factory()->create([
            'venue_id' => $venue->id,
            'service_location_id' => $location->id,
            'type' => ServiceRequestType::Message,
            'message' => 'Preciso de um copo',
        ]);
        ServiceRequest::factory()->acknowledged()->create([
            'venue_id' => $venue->id,
            'type' => ServiceRequestType::Message,
            'message' => 'Ainda esperando',
        ]);
        ServiceRequest::factory()->resolved()->create([
            'venue_id' => $venue->id,
            'type' => ServiceRequestType::Message,
        ]);
        ServiceRequest::factory()->callToOrder()->create(['venue_id' => $venue->id]);
        ServiceRequest::factory()->create([
            'venue_id' => Venue::factory()->create()->id,
            'type' => ServiceRequestType::Message,
        ]);

        $this->get(route('kitchen.kds'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('waiterCalls', 2)
                ->where('waiterCalls', function ($calls) use ($mine) {
                    $collection = collect($calls);

                    return $collection->contains(fn ($call) => $call['id'] === $mine->id
                        && $call['message'] === 'Preciso de um copo'
                        && $call['service_location']['name'] === 'Mesa 7')
                        && $collection->contains(fn ($call) => $call['message'] === 'Ainda esperando');
                }));
    }

    public function test_venue_defaults_include_the_always_active_waiter_caller_station(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        app(CreateVenueDefaultsAction::class)->execute($venue);

        $this->assertDatabaseHas('kitchen_stations', [
            'venue_id' => $venue->id,
            'code' => KitchenStationCode::WaiterCaller->value,
            'name' => KitchenStationCode::WaiterCaller->defaultName(),
            'active' => true,
        ]);
        $this->assertDatabaseHas('kitchen_stations', [
            'venue_id' => $venue->id,
            'name' => 'Cozinha',
        ]);
        $this->assertDatabaseHas('kitchen_stations', [
            'venue_id' => $venue->id,
            'name' => 'Bar',
        ]);
    }

    public function test_kds_still_loads_when_opened_as_a_dedicated_display(): void
    {
        $venue = Venue::factory()->create();
        $this->enableKdsModule($venue);
        $this->loginAs(UserRole::Attendant, $venue);

        $station = app(EnsureWaiterCallerKitchenStationAction::class)->execute($venue);

        $this->get(route('kitchen.kds', [
            'display' => 1,
            'stations' => $station->id,
        ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kitchen/Kds')
                ->has('stations')
                ->where('stations.0.id', $station->id));
    }
}
