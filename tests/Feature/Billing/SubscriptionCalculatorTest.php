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
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\PlanCatalogVersion;
use App\Models\Tenant\PlanModuleUsageTier;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenuePlanAssignment;
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
        $venue = $this->createVenueWithSubscription(baseValue: 9990);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertSame(9990, $result['base']);
        $this->assertSame(0, $result['modules']);
        $this->assertSame(0, $result['metered']);
        $this->assertSame(9990, $result['total']);
        $this->assertDatabaseHas('venue_subscriptions', [
            'id' => $venue->subscription->id,
            'base_value' => 9990,
            'total_value' => 9990,
        ]);
    }

    public function test_calculate_venue_uses_the_minimum_commitment_from_the_assigned_plan_version(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 9990);
        $plan = PlanCatalog::factory()->create();
        $version = PlanCatalogVersion::factory()->create([
            'plan_catalog_id' => $plan->id,
            'effective_from' => '2026-01-01',
            'minimum_monthly_price' => 24900,
        ]);
        VenuePlanAssignment::factory()->create([
            'venue_id' => $venue->id,
            'plan_catalog_id' => $plan->id,
            'plan_catalog_version_id' => $version->id,
            'starts_on' => '2026-01-01',
        ]);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertSame(24900, $result['base']);
        $this->assertSame(24900, $result['total']);

        $this->calculator->refreshVenueSnapshot($venue, '2026-07');

        $this->assertDatabaseHas('venue_subscriptions', [
            'id' => $venue->subscription->id,
            'base_value' => 24900,
            'total_value' => 24900,
        ]);
    }

    public function test_calculate_venue_with_fixed_modules(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 5000);
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 4990);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertSame(5000, $result['base']);
        $this->assertSame(4990, $result['modules']);
        $this->assertSame(9990, $result['total']);
    }

    public function test_calculate_venue_with_custom_corporate_price(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 5000);
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 4990, customPrice: 3990);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertSame(3990, $result['modules']);
        $this->assertSame(8990, $result['total']);
    }

    public function test_calculate_venue_with_metered_within_included_quantity(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 5000);
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 4990);
        $this->createUsageTier(ModuleCode::Kds, includedQuantity: 500);
        $this->createUsageRecord($venue, ModuleCode::Kds, '2026-06', quantity: 300);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertSame(4990, $result['modules']);
        $this->assertSame(0, $result['metered']);
        $this->assertSame(9990, $result['total']);
    }

    public function test_calculate_venue_with_metered_overage(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 5000);
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 4990);
        $this->createUsageTier(ModuleCode::Kds, includedQuantity: 500, overagePricePerUnit: 1000);
        $this->createUsageRecord($venue, ModuleCode::Kds, '2026-06', quantity: 700);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertSame(4990, $result['modules']);
        $this->assertSame(2000, $result['metered']);
        $this->assertSame(11990, $result['total']);
    }

    public function test_metered_is_billed_from_the_previous_closed_period(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 5000);
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 4990);
        $this->createUsageTier(ModuleCode::Kds, includedQuantity: 500, overagePricePerUnit: 1000);

        // Consumo do próprio mês da fatura ainda está aberto e não deve entrar:
        // a fatura de 2026-07 é emitida no dia 01, quando o mês mal começou.
        $this->createUsageRecord($venue, ModuleCode::Kds, '2026-07', quantity: 900);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertSame(0, $result['metered']);
    }

    public function test_metered_is_not_charged_for_modules_the_venue_never_contracted(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 5000);
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 4990);
        $this->createUsageTier(ModuleCode::Taker, includedQuantity: 0, overagePricePerUnit: 1000);
        $this->createUsageRecord($venue, ModuleCode::Taker, '2026-06', quantity: 700);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertSame(0, $result['metered']);
        $this->assertSame(9990, $result['total']);
    }

    public function test_metered_uses_graduated_tiers(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 5000);
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 4990);
        $this->createGraduatedTiers(ModuleCode::Kds);
        $this->createUsageRecord($venue, ModuleCode::Kds, '2026-06', quantity: 1500);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        // 1.000 unidades na primeira faixa (R$ 0,05) + 500 na segunda (R$ 0,03).
        $this->assertSame(5000 + 1500, $result['metered']);
    }

    public function test_metered_uses_the_plan_version_assigned_to_each_venue(): void
    {
        $smallVenue = $this->createVenueWithSubscription(baseValue: 0);
        $largeVenue = $this->createVenueWithSubscription(baseValue: 0);

        foreach ([$smallVenue, $largeVenue] as $venue) {
            $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 0);
            $this->createUsageRecord($venue, ModuleCode::Kds, '2026-06', quantity: 300);
        }

        $smallPlan = PlanCatalog::factory()->create(['code' => 'small']);
        $largePlan = PlanCatalog::factory()->create(['code' => 'large']);
        $smallVersion = PlanCatalogVersion::factory()->create([
            'plan_catalog_id' => $smallPlan->id,
            'effective_from' => '2026-01-01',
        ]);
        $largeVersion = PlanCatalogVersion::factory()->create([
            'plan_catalog_id' => $largePlan->id,
            'effective_from' => '2026-01-01',
        ]);

        PlanModuleUsageTier::factory()->create([
            'plan_catalog_version_id' => $smallVersion->id,
            'included_quantity' => 100,
            'overage_price_per_unit' => 1000,
        ]);
        PlanModuleUsageTier::factory()->create([
            'plan_catalog_version_id' => $largeVersion->id,
            'included_quantity' => 300,
            'overage_price_per_unit' => 500,
        ]);

        $smallAssignment = VenuePlanAssignment::factory()->create([
            'venue_id' => $smallVenue->id,
            'plan_catalog_id' => $smallPlan->id,
            'plan_catalog_version_id' => $smallVersion->id,
            'starts_on' => '2026-01-01',
        ]);
        $largeAssignment = VenuePlanAssignment::factory()->create([
            'venue_id' => $largeVenue->id,
            'plan_catalog_id' => $largePlan->id,
            'plan_catalog_version_id' => $largeVersion->id,
            'starts_on' => '2026-01-01',
        ]);

        $this->assertSame(2000, $this->calculator->calculateVenue($smallVenue, '2026-07')['metered']);
        $this->assertSame(0, $this->calculator->calculateVenue($largeVenue, '2026-07')['metered']);

        $this->assertDatabaseHas('venue_usage_records', [
            'venue_id' => $smallVenue->id,
            'venue_plan_assignment_id' => $smallAssignment->id,
            'plan_catalog_version_id' => $smallVersion->id,
            'overage_quantity' => 200,
        ]);
        $this->assertDatabaseHas('venue_usage_records', [
            'venue_id' => $largeVenue->id,
            'venue_plan_assignment_id' => $largeAssignment->id,
            'plan_catalog_version_id' => $largeVersion->id,
            'overage_quantity' => 0,
        ]);
    }

    public function test_metered_price_never_decreases_when_quantity_grows(): void
    {
        $this->createGraduatedTiers(ModuleCode::Kds);

        $previousTotal = 0;

        foreach ([0, 500, 999, 1000, 1001, 1500, 3000] as $quantity) {
            $venue = $this->createVenueWithSubscription(baseValue: 0);
            $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 0);
            $this->createUsageRecord($venue, ModuleCode::Kds, '2026-06', quantity: $quantity);

            $total = $this->calculator->calculateVenue($venue, '2026-07')['metered'];

            $this->assertGreaterThanOrEqual(
                $previousTotal,
                $total,
                "Consumir {$quantity} unidades ficou mais barato que a quantidade anterior."
            );

            $previousTotal = $total;
        }
    }

    public function test_metered_record_totals_match_the_sum_of_its_parts(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 5000);
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 4990);
        $this->createGraduatedTiers(ModuleCode::Kds);
        $this->createUsageRecord($venue, ModuleCode::Kds, '2026-06', quantity: 1237);

        $metered = $this->calculator->calculateVenue($venue, '2026-07')['metered'];

        $record = VenueUsageRecord::where('venue_id', $venue->id)->firstOrFail();

        $this->assertSame(
            (int) $record->base_calculated_price + (int) $record->overage_calculated_price,
            (int) $record->total_calculated_price
        );
        $this->assertSame((int) $record->total_calculated_price, $metered);
        $this->assertSame(1237, (int) $record->included_quantity + (int) $record->overage_quantity);
    }

    public function test_module_activated_mid_period_is_charged_proportionally(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 0);
        // Ativo de 17/07 a 31/07 = 15 dos 31 dias de julho.
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 3100, startedAt: '2026-07-17');

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertSame(1500, $result['modules']);
    }

    public function test_module_canceled_mid_period_is_still_charged_for_the_days_used(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 0);
        // Sem proration este cenário — ativar dia 2 e cancelar dia 28 — nunca
        // era faturado: o módulo já saía da janela de módulos ativos.
        $this->enableModuleForVenue(
            $venue,
            ModuleCode::Kds,
            basePrice: 3100,
            startedAt: '2026-07-02',
            endedAt: '2026-07-28',
        );

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertSame(2700, $result['modules']);
    }

    public function test_module_outside_the_period_is_not_charged(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 0);
        $this->enableModuleForVenue(
            $venue,
            ModuleCode::Kds,
            basePrice: 3100,
            startedAt: '2026-05-01',
            endedAt: '2026-06-30',
        );

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertSame(0, $result['modules']);
    }

    public function test_calculate_venue_with_metered_overage_does_not_double_charge_included_quantity(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 5000);
        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 4990);
        $this->createUsageTier(ModuleCode::Kds, includedQuantity: 500, overagePricePerUnit: 1000, pricePerUnit: 500);
        $this->createUsageRecord($venue, ModuleCode::Kds, '2026-06', quantity: 700);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        // Esperado: 500 unidades inclusas * 0.05 (base) + 200 unidades excedentes * 0.10 (overage) = 25 + 20 = 45.
        // Antes da correção, o cálculo cobrava price_per_unit sobre as 700 unidades (35) mais o excedente (20) = 55.
        $this->assertSame(4500, $result['metered']);
        $this->assertSame(14490, $result['total']);
    }

    public function test_calculate_venue_with_dedicated_surcharge(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 5000, dedicatedSurcharge: 2500);

        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertSame(2500, $result['dedicated_surcharge']);
        $this->assertSame(7500, $result['total']);
    }

    public function test_refresh_venue_snapshot_signals_closed_period_when_invoice_finalized(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 5000);
        VenueInvoice::factory()->create([
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'is_finalized' => true,
            'status' => InvoiceStatus::Paid,
        ]);

        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 4990);

        $this->assertNull($this->calculator->refreshVenueSnapshot($venue, '2026-07'));

        // O cálculo puro continua disponível: o período fechado só impede o
        // refaturamento.
        $result = $this->calculator->calculateVenue($venue, '2026-07');

        $this->assertSame(5000, $result['base']);
        $this->assertSame(4990, $result['modules']);
    }

    public function test_refresh_venue_snapshot_still_updates_subscription_when_invoice_finalized(): void
    {
        $venue = $this->createVenueWithSubscription(baseValue: 10000);
        VenueInvoice::factory()->create([
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'is_finalized' => true,
            'status' => InvoiceStatus::Paid,
            'base_value' => 10000,
            'total_value' => 10000,
        ]);

        $this->enableModuleForVenue($venue, ModuleCode::Kds, basePrice: 4990);

        $this->calculator->refreshVenueSnapshot($venue, '2026-07');

        $this->assertDatabaseHas('venue_subscriptions', [
            'id' => $venue->subscription->id,
            'base_value' => 10000,
            'modules_value' => 4990,
            'metered_value' => 0,
            'total_value' => 14990,
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

        $venueA = $this->createVenueForCorporation($corporation, baseValue: 5000);
        $venueB = $this->createVenueForCorporation($corporation, baseValue: 5000);
        $this->enableModuleForVenue($venueA, ModuleCode::Kds, basePrice: 4990);
        $this->enableModuleForVenue($venueB, ModuleCode::Kds, basePrice: 4990);

        $result = $this->calculator->calculateCorporation($corporation, '2026-07');

        $this->assertCount(2, $result['venues']);
        $this->assertSame(19980, $result['total']);
    }

    private function createVenueWithSubscription(int $baseValue, int $dedicatedSurcharge = 0): Venue
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

    private function createVenueForCorporation(Corporation $corporation, int $baseValue): Venue
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

    private function enableModuleForVenue(
        Venue $venue,
        ModuleCode $code,
        int $basePrice,
        ?int $customPrice = null,
        string $startedAt = '2026-06-01',
        ?string $endedAt = null,
    ): void {
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
                'started_at' => '2026-01-01',
            ]
        );

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => $code->value,
            'status' => $endedAt === null ? ModuleStatus::Active : ModuleStatus::Inactive,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
        ]);
    }

    /**
     * Os preços das faixas são armazenados em centésimos de centavo (1e-4 BRL).
     */
    private function createUsageTier(ModuleCode $code, int $includedQuantity, int $overagePricePerUnit = 0, int $pricePerUnit = 0): void
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

    /**
     * Tabela graduada: até 1.000 unidades a R$ 0,05 e o excedente a R$ 0,03.
     */
    private function createGraduatedTiers(ModuleCode $code): void
    {
        ModuleUsageTier::create([
            'module_code' => $code->value,
            'min_quantity' => 0,
            'max_quantity' => 1000,
            'included_quantity' => 1000,
            'price_per_unit' => 500,
            'overage_price_per_unit' => 0,
        ]);

        ModuleUsageTier::create([
            'module_code' => $code->value,
            'min_quantity' => 1001,
            'max_quantity' => null,
            'included_quantity' => 0,
            'price_per_unit' => 0,
            'overage_price_per_unit' => 300,
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
