<?php

namespace Tests\Feature\Guest\Delivery;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Orders\Attendance;
use App\Models\Settings\DeliveryFeeZone;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class PlaceDeliveryOrderTest extends TestCase
{
    use RefreshAllDatabases;

    private function makeToken(Venue $venue): string
    {
        return rtrim(base64_encode(json_encode(['v' => $venue->id])), '=');
    }

    private function activateDelivery(Venue $venue): void
    {
        // firstOrCreate: dois venues da mesma corporation compartilham a linha
        // de CorporationModule (unique constraint em corporation_id+module_code).
        CorporationModule::firstOrCreate(
            ['corporation_id' => $venue->corporation_id, 'module_code' => ModuleCode::Delivery->value],
            ['status' => ModuleStatus::Active, 'started_at' => now()]
        );

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Delivery->value,
            'status' => ModuleStatus::Active,
        ]);
    }

    private function makeDeliverableProduct(Venue $venue): Product
    {
        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);

        return Product::factory()->create([
            'category_id' => $category->id,
            'price' => 20,
            'active' => true,
            'available_for_delivery' => true,
        ]);
    }

    private function basePayload(Product $product): array
    {
        return [
            'fulfillment_type' => 'pickup',
            'customer' => ['name' => 'Maria Silva', 'phone' => '11999998888'],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'methods' => [
                ['type' => 'cash', 'amount' => 40],
            ],
        ];
    }

    public function test_returns_404_when_delivery_module_is_inactive(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $product = $this->makeDeliverableProduct($venue);
        $token = $this->makeToken($venue);

        $this->postJson("/delivery/{$token}/orders", $this->basePayload($product))->assertStatus(404);
    }

    public function test_guest_can_place_a_pickup_order(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        VenueSettings::factory()->create([
            'venue_id' => $venue->id,
            'service_fee_percent' => 0,
            'accepted_delivery_payment_methods' => ['cash', 'pix'],
        ]);
        $product = $this->makeDeliverableProduct($venue);
        $token = $this->makeToken($venue);

        $response = $this->postJson("/delivery/{$token}/orders", $this->basePayload($product));

        $response->assertCreated();
        $this->assertDatabaseHas('customers', [
            'corporation_id' => $venue->corporation_id,
            'phone' => '11999998888',
        ]);

        $orderId = $response->json('order_id');
        $attendance = Attendance::withoutGlobalScopes()
            ->whereHas('orders', fn ($q) => $q->where('id', $orderId))
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals('open', $attendance->status->value);
        $this->assertNotNull($attendance->payment);
        $this->assertEquals(40, (float) $attendance->payment->grand_total);
    }

    public function test_guest_can_place_a_delivery_order_within_a_fee_zone(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        VenueSettings::factory()->create([
            'venue_id' => $venue->id,
            'service_fee_percent' => 0,
            'accepted_delivery_payment_methods' => ['cash'],
        ]);
        DeliveryFeeZone::factory()->create([
            'venue_id' => $venue->id,
            'zip_code_start' => 1000000,
            'zip_code_end' => 1999999,
            'fee' => 10,
        ]);
        $product = $this->makeDeliverableProduct($venue);
        $token = $this->makeToken($venue);

        $payload = $this->basePayload($product);
        $payload['fulfillment_type'] = 'delivery';
        $payload['address'] = [
            'street' => 'Rua A', 'number' => '100', 'neighborhood' => 'Centro',
            'city' => 'São Paulo', 'state' => 'SP', 'zip_code' => '01310100',
        ];
        $payload['methods'] = [['type' => 'cash', 'amount' => 50]];

        $response = $this->postJson("/delivery/{$token}/orders", $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('delivery_orders', [
            'venue_id' => $venue->id,
            'fulfillment_type' => 'delivery',
            'delivery_fee' => 10,
        ]);
    }

    public function test_delivery_order_is_rejected_when_zip_code_is_outside_any_fee_zone(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        VenueSettings::factory()->create([
            'venue_id' => $venue->id,
            'service_fee_percent' => 0,
            'accepted_delivery_payment_methods' => ['cash'],
        ]);
        $product = $this->makeDeliverableProduct($venue);
        $token = $this->makeToken($venue);

        $payload = $this->basePayload($product);
        $payload['fulfillment_type'] = 'delivery';
        $payload['address'] = [
            'street' => 'Rua A', 'number' => '100', 'neighborhood' => 'Centro',
            'city' => 'São Paulo', 'state' => 'SP', 'zip_code' => '01310100',
        ];

        $response = $this->postJson("/delivery/{$token}/orders", $payload);

        $response->assertStatus(422);
    }

    public function test_order_is_rejected_when_payment_methods_do_not_match_total(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        VenueSettings::factory()->create([
            'venue_id' => $venue->id,
            'service_fee_percent' => 0,
            'accepted_delivery_payment_methods' => ['cash'],
        ]);
        $product = $this->makeDeliverableProduct($venue);
        $token = $this->makeToken($venue);

        $payload = $this->basePayload($product);
        $payload['methods'] = [['type' => 'cash', 'amount' => 1]];

        $response = $this->postJson("/delivery/{$token}/orders", $payload);

        $response->assertStatus(422);
    }

    public function test_product_not_available_for_delivery_is_rejected(): void
    {
        $venue = Venue::factory()->create(['active' => true]);
        $this->activateDelivery($venue);
        VenueSettings::factory()->create([
            'venue_id' => $venue->id,
            'accepted_delivery_payment_methods' => ['cash'],
        ]);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'available_for_delivery' => false,
        ]);
        $token = $this->makeToken($venue);

        $response = $this->postJson("/delivery/{$token}/orders", $this->basePayload($product));

        $response->assertStatus(422);
    }

    public function test_existing_customer_is_reused_across_orders_in_the_same_corporation(): void
    {
        $corporation = Corporation::factory()->create();
        $venueA = Venue::factory()->create(['active' => true, 'corporation_id' => $corporation->id]);
        $venueB = Venue::factory()->create(['active' => true, 'corporation_id' => $corporation->id]);
        $this->activateDelivery($venueA);
        $this->activateDelivery($venueB);
        VenueSettings::factory()->create(['venue_id' => $venueA->id, 'service_fee_percent' => 0, 'accepted_delivery_payment_methods' => ['cash']]);
        VenueSettings::factory()->create(['venue_id' => $venueB->id, 'service_fee_percent' => 0, 'accepted_delivery_payment_methods' => ['cash']]);

        $productA = $this->makeDeliverableProduct($venueA);
        $productB = $this->makeDeliverableProduct($venueB);

        $this->postJson('/delivery/'.$this->makeToken($venueA).'/orders', $this->basePayload($productA))->assertCreated();
        $this->postJson('/delivery/'.$this->makeToken($venueB).'/orders', $this->basePayload($productB))->assertCreated();

        $this->assertEquals(1, Customer::withoutGlobalScopes()
            ->where('corporation_id', $corporation->id)
            ->where('phone', '11999998888')
            ->count());
    }
}
