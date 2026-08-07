<?php

namespace Tests\Feature\Billing;

use App\Enums\BillingMode;
use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\Billing\GenerateInvoicesJob;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenueSubscription;
use App\Services\Billing\SubscriptionCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class GenerateInvoicesPerformanceTest extends TestCase
{
    use RefreshAllDatabases;

    /**
     * Sem memoização e eager loading, cada venue repetia as mesmas consultas de
     * catálogo e o custo da geração mensal crescia linearmente com a base.
     */
    public function test_query_count_stays_within_budget(): void
    {
        Notification::fake();

        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Active,
            'billing_day' => 10,
        ]);

        $this->registerModuleCatalog();

        foreach (range(1, 20) as $ignored) {
            $venue = $this->createVenue($corporation);
            $this->enableModule($corporation, $venue);
        }

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        // O que sobra é escrita por venue (fatura, itens e snapshot); catálogo,
        // módulos da corporation e faixas de consumo são lidos uma única vez.
        $this->assertLessThan(
            300,
            $queries,
            "A geração de faturas para 20 venues executou {$queries} queries."
        );
    }

    public function test_defaults_of_the_calculator_are_not_memoized_outside_a_batch(): void
    {
        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Active,
            'billing_day' => 10,
        ]);

        $this->registerModuleCatalog();

        $venue = $this->createVenue($corporation);
        $calculator = new SubscriptionCalculator;

        $this->assertSame(0, $calculator->calculateVenue($venue, '2026-07')['modules']);

        $this->enableModule($corporation, $venue);

        $this->assertSame(4990, $calculator->calculateVenue($venue->fresh(), '2026-07')['modules']);
    }

    private function registerModuleCatalog(): void
    {
        ModuleCatalog::firstOrCreate(
            ['code' => ModuleCode::Kds->value],
            [
                'name' => ModuleCode::Kds->label(),
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => 4990,
                'dependencies' => [ModuleCode::Menu->value],
                'active' => true,
                'sort_order' => 1,
            ]
        );
    }

    private function createVenue(Corporation $corporation): Venue
    {
        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);

        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $corporation->subscription->id,
            'base_value' => 5000,
            'total_value' => 5000,
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => null,
        ]);

        return $venue->fresh();
    }

    private function enableModule(Corporation $corporation, Venue $venue): void
    {
        CorporationModule::firstOrCreate(
            [
                'corporation_id' => $corporation->id,
                'module_code' => ModuleCode::Kds->value,
            ],
            [
                'status' => ModuleStatus::Active,
                'started_at' => '2026-01-01',
            ]
        );

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Kds->value,
            'status' => ModuleStatus::Active,
            'started_at' => '2026-01-01',
        ]);
    }
}
