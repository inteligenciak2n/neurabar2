<?php

namespace Tests\Feature\Billing;

use App\Enums\BillingMode;
use App\Enums\InvoiceStatus;
use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\ModuleUsageTier;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenueSubscription;
use App\Models\Tenant\VenueUsageRecord;
use App\Services\Billing\SubscriptionCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:migrate-all --fresh --force');
        $this->calculator = new SubscriptionCalculator;
    }

    public function test_calculate_venue_with_base_only(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 99.90);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertEqualsWithDelta(99.90, $result['base'], 0.01);
        $this->assertEqualsWithDelta(0.0, $result['modules'], 0.01);
        $this->assertEqualsWithDelta(0.0, $result['metered'], 0.01);
        $this->assertEqualsWithDelta(99.90, $result['total'], 0.01);
        $this->assertDatabaseHas('venue_subscriptions', [
            'id' => $venue->subscription->id,
            'base_value' => 99.90,
            'total_value' => 99.90,
        ]);
    }

    public function test_calculate_venue_with_fixed_modules(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 50.00);
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 49.90);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertEqualsWithDelta(50.00, $result['base'], 0.01);
        $this->assertEqualsWithDelta(49.90, $result['modules'], 0.01);
        $this->assertEqualsWithDelta(99.90, $result['total'], 0.01);
    }

    public function test_calculate_venue_with_custom_corporate_price(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 50.00);
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 49.90, customPrice: 39.90);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertEqualsWithDelta(39.90, $result['modules'], 0.01);
        $this->assertEqualsWithDelta(89.90, $result['total'], 0.01);
    }

    public function test_calculate_venue_with_metered_within_included_quantity(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 50.00);
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 49.90);
        $this->createUsageTier(ModuleCode::Kds, includedQuantity: 500);
        $this->createUsageRecord($venue, ModuleCode::Kds, '2026-07', quantity: 300);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertEqualsWithDelta(49.90, $result['modules'], 0.01);
        $this->assertEqualsWithDelta(0.0, $result['metered'], 0.01);
        $this->assertEqualsWithDelta(99.90, $result['total'], 0.01);
    }

    public function test_calculate_venue_with_metered_overage(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 50.00);
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 49.90);
        $this->createUsageTier(ModuleCode::Kds, includedQuantity: 500, overagePricePerUnit: 0.10);
        $this->createUsageRecord($venue, ModuleCode::Kds, '2026-07', quantity: 700);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertEqualsWithDelta(49.90, $result['modules'], 0.01);
        $this->assertEqualsWithDelta(20.00, $result['metered'], 0.01);
        $this->assertEqualsWithDelta(119.90, $result['total'], 0.01);
    }

    public function test_calculate_venue_with_metered_overage_does_not_double_charge_included_quantity(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 50.00);
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 49.90);
        $this->createUsageTier(ModuleCode::Kds, includedQuantity: 500, overagePricePerUnit: 0.10, pricePerUnit: 0.05);
        $this->createUsageRecord($venue, ModuleCode::Kds, '2026-07', quantity: 700);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        // Esperado: 500 unidades inclusas * 0.05 (base) + 200 unidades excedentes * 0.10 (overage) = 25 + 20 = 45.
        // Antes da correção, o cálculo cobrava price_per_unit sobre as 700 unidades (35) mais o excedente (20) = 55.
        $this->assertEqualsWithDelta(45.00, $result['metered'], 0.01);
        $this->assertEqualsWithDelta(144.90, $result['total'], 0.01);
    }

    public function test_calculate_venue_with_dedicated_surcharge(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 50.00, dedicatedSurcharge: 25.00);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertEqualsWithDelta(25.00, $result['dedicated_surcharge'], 0.01);
        $this->assertEqualsWithDelta(75.00, $result['total'], 0.01);
    }

    public function test_calculate_venue_skips_when_invoice_finalized(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 50.00);
        VenueInvoice::factory()->create([
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'is_finalized' => true,
            'status' => InvoiceStatus::Paid,
        ]);

        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 49.90);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertNull($result);
    }

    public function test_calculate_venue_does_not_update_subscription_when_invoice_finalized(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 100.00);
        VenueInvoice::factory()->create([
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'is_finalized' => true,
            'status' => InvoiceStatus::Paid,
            'base_value' => 100.00,
            'total_value' => 100.00,
        ]);

        $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertDatabaseHas('venue_subscriptions', [
            'id' => $venue->subscription->id,
            'base_value' => 100.00,
            'modules_value' => 0.0,
            'metered_value' => 0.0,
            'total_value' => 100.00,
        ]);
    }

    public function test_calculate_corporation_unified(): void
    {
        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Active,
        ]);

        $venueA = $this->createVenueForCorporation($corporation, baseValue: 50.00);
        $venueB = $this->createVenueForCorporation($corporation, baseValue: 50.00);
        $this->enableModuleForVenue($venueA, ModuleCode::Kds, basePrice: 49.90);
        $this->enableModuleForVenue($venueB, ModuleCode::Kds, basePrice: 49.90);

        $result = $this->calculator->calculateCorporation($corporation, '2026-07');

        $this->assertCount(2, $result['venues']);
        $this->assertEqualsWithDelta(199.80, $result['total'], 0.01);
    }

    private function createVenueWithSubscription(float $baseValue, float $dedicatedSurcharge = 0.0): Venue
    {
        $venue = Venue::factory()->create();
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'base_value' => $baseValue,
            'dedicated_surcharge' => $dedicatedSurcharge,
            'total_value' => $baseValue + $dedicatedSurcharge,
            'status' => SubscriptionStatus::Active,
        ]);

        return $venue->fresh();
    }

    private function createVenueForCorporation(Corporation $corporation, float $baseValue): Venue
    {
        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $corporation->subscription->id,
            'base_value' => $baseValue,
            'total_value' => $baseValue,
            'status' => SubscriptionStatus::Active,
        ]);

        return $venue->fresh();
    }

    private function enableModuleForVenue(Venue $venue, ModuleCode $code, float $basePrice, ?float $customPrice = null): void
    {
        ModuleCatalog::firstOrCreate(
            ['code' => $code->value],
            [
                'name' => $code->label(),
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => $basePrice,
                'dependencies' => [ModuleCode::Menu->value],
                'active' => true,
                'sort_order' => 1,
            ]
        );

        CorporationModule::firstOrCreate(
            [
                'corporation_id' => $venue->corporation_id,
                'module_code' => $code->value,
            ],
            [
                'status' => ModuleStatus::Active,
                'custom_monthly_price' => $customPrice,
                'started_at' => now(),
            ]
        );

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => $code->value,
            'status' => ModuleStatus::Active,
        ]);
    }

    private function createUsageTier(ModuleCode $code, int $includedQuantity, float $overagePricePerUnit = 0.0, float $pricePerUnit = 0.0): void
    {
        ModuleUsageTier::create([
            'module_code' => $code->value,
            'min_quantity' => 0,
            'max_quantity' => null,
            'included_quantity' => $includedQuantity,
            'price_per_unit' => $pricePerUnit,
            'overage_price_per_unit' => $overagePricePerUnit,
        ]);
    }

    private function createUsageRecord(Venue $venue, ModuleCode $code, string $period, int $quantity): void
    {
        VenueUsageRecord::create([
            'venue_id' => $venue->id,
            'module_code' => $code->value,
            'period' => $period,
            'quantity' => $quantity,
        ]);
    }
}
