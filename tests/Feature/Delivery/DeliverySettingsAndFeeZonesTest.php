<?php

namespace Tests\Feature\Delivery;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\UserRole;
use App\Models\Settings\DeliveryFeeZone;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class DeliverySettingsAndFeeZonesTest extends TestCase
{
    use RefreshAllDatabases;

    private function enableDeliveryModule(Venue $venue): void
    {
        CorporationModule::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'module_code' => ModuleCode::Delivery->value,
            'status' => ModuleStatus::Active,
        ]);

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Delivery->value,
            'status' => ModuleStatus::Active,
        ]);
    }

    public function test_dashboard_returns_public_link_and_settings(): void
    {
        $venue = Venue::factory()->create();
        $this->enableDeliveryModule($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $this->get(route('delivery.index'))->assertOk()->assertInertia(
            fn ($page) => $page->component('Delivery/Index')
                ->has('deliveryLink')
                ->has('feeZones')
                ->has('settings')
        );
    }

    public function test_staff_can_create_update_and_delete_a_fee_zone(): void
    {
        $venue = Venue::factory()->create();
        $this->enableDeliveryModule($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $this->post(route('delivery.fee-zones.store'), [
            'label' => 'Centro',
            'zip_code_start' => '01000000',
            'zip_code_end' => '01999999',
            'fee' => 12.5,
        ])->assertRedirect();

        $zone = DeliveryFeeZone::where('venue_id', $venue->id)->firstOrFail();
        $this->assertEquals(12.5, (float) $zone->fee);

        $this->put(route('delivery.fee-zones.update', $zone->id), [
            'label' => 'Centro',
            'zip_code_start' => '01000000',
            'zip_code_end' => '01999999',
            'fee' => 15,
        ])->assertRedirect();

        $this->assertEquals(15, (float) $zone->fresh()->fee);

        $this->delete(route('delivery.fee-zones.destroy', $zone->id))->assertRedirect();
        $this->assertDatabaseMissing('delivery_fee_zones', ['id' => $zone->id]);
    }

    public function test_staff_can_update_delivery_settings(): void
    {
        $venue = Venue::factory()->create();
        $this->enableDeliveryModule($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $this->put(route('delivery.settings.update'), [
            'accepted_delivery_payment_methods' => ['cash', 'pix'],
            'delivery_enabled' => true,
            'pickup_enabled' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('venue_settings', [
            'venue_id' => $venue->id,
            'delivery_enabled' => true,
            'pickup_enabled' => false,
        ]);
    }
}
