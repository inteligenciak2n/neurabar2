<?php

namespace Tests\Feature\Billing\Jobs;

use App\Enums\BillingMode;
use App\Enums\InvoiceStatus;
use App\Enums\ModuleBillingType;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\Billing\GenerateInvoicesJob;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenueSubscription;
use App\Notifications\Billing\InvoiceGenerated;
use App\Services\Billing\SubscriptionCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GenerateInvoicesJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:migrate-all --fresh --force');
    }

    public function test_generates_venue_invoice_for_active_subscription(): void
    {
        $venue = $this->createActiveVenue(baseValue: 99.90);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'status' => InvoiceStatus::Open->value,
            'base_value' => 99.90,
            'total_value' => 99.90,
        ]);
    }

    public function test_sends_invoice_generated_notification_to_owner(): void
    {
        Notification::fake();

        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Active,
            'billing_day' => 10,
        ]);

        $venue = $this->createVenueForCorporation($corporation, baseValue: 50.00);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        Notification::assertSentTo(
            $corporation->owner,
            InvoiceGenerated::class
        );
    }

    public function test_generates_corporation_invoice_for_unified_mode(): void
    {
        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Active,
            'billing_day' => 10,
        ]);

        $venueA = $this->createVenueForCorporation($corporation, baseValue: 50.00);
        $venueB = $this->createVenueForCorporation($corporation, baseValue: 50.00);
        $this->enableModule($corporation, $venueA, ModuleCode::Kds, 49.90);
        $this->enableModule($corporation, $venueB, ModuleCode::Kds, 49.90);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $corporationInvoice = CorporationInvoice::query()
            ->where('corporation_id', $corporation->id)
            ->where('period', '2026-07')
            ->first();

        $this->assertNotNull($corporationInvoice);
        $this->assertSame(199.80, (float) $corporationInvoice->total_value);

        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venueA->id,
            'period' => '2026-07',
            'corporation_invoice_id' => $corporationInvoice->id,
        ]);

        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venueB->id,
            'period' => '2026-07',
            'corporation_invoice_id' => $corporationInvoice->id,
        ]);
    }

    public function test_does_not_duplicate_invoices_for_same_period(): void
    {
        $venue = $this->createActiveVenue(baseValue: 99.90);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);
        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseCount('venue_invoices', 1);
    }

    public function test_does_not_generate_for_finalized_venue_invoice(): void
    {
        $venue = $this->createActiveVenue(baseValue: 99.90);
        VenueInvoice::factory()->create([
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'is_finalized' => true,
            'status' => InvoiceStatus::Paid,
            'base_value' => 99.90,
            'modules_value' => 0.0,
            'metered_value' => 0.0,
            'total_value' => 99.90,
        ]);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseCount('venue_invoices', 1);
        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'base_value' => 99.90,
            'modules_value' => 0.0,
            'metered_value' => 0.0,
            'total_value' => 99.90,
        ]);
    }

    public function test_does_not_overwrite_finalized_corporation_invoice(): void
    {
        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Active,
            'billing_day' => 10,
        ]);
        $venue = $this->createVenueForCorporation($corporation, baseValue: 50.00);
        $this->enableModule($corporation, $venue, ModuleCode::Kds, 49.90);

        CorporationInvoice::factory()->create([
            'corporation_id' => $corporation->id,
            'period' => '2026-07',
            'is_finalized' => true,
            'status' => InvoiceStatus::Paid,
            'base_value' => 50.00,
            'modules_value' => 49.90,
            'metered_value' => 0.0,
            'total_value' => 99.90,
        ]);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseHas('corporation_invoices', [
            'corporation_id' => $corporation->id,
            'period' => '2026-07',
            'base_value' => 50.00,
            'modules_value' => 49.90,
            'metered_value' => 0.0,
            'total_value' => 99.90,
        ]);
    }

    public function test_does_not_overwrite_finalized_venue_invoice_with_active_modules(): void
    {
        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Active,
            'billing_day' => 10,
        ]);
        $venue = $this->createVenueForCorporation($corporation, baseValue: 50.00);
        $this->enableModule($corporation, $venue, ModuleCode::Kds, 49.90);

        VenueInvoice::factory()->create([
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'is_finalized' => true,
            'status' => InvoiceStatus::Paid,
            'base_value' => 50.00,
            'modules_value' => 49.90,
            'metered_value' => 0.0,
            'total_value' => 99.90,
        ]);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'base_value' => 50.00,
            'modules_value' => 49.90,
            'metered_value' => 0.0,
            'total_value' => 99.90,
        ]);
    }

    public function test_sets_due_date_based_on_billing_day(): void
    {
        $venue = $this->createActiveVenue(baseValue: 99.90, billingDay: 15);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'due_date' => '2026-07-15',
        ]);
    }

    private function createActiveVenue(float $baseValue, int $billingDay = 1): Venue
    {
        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Active,
            'billing_day' => $billingDay,
        ]);

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

    private function enableModule(Corporation $corporation, Venue $venue, ModuleCode $code, float $price): void
    {
        ModuleCatalog::firstOrCreate(
            ['code' => $code->value],
            [
                'name' => $code->label(),
                'billing_type' => ModuleBillingType::Hybrid,
                'base_monthly_price' => $price,
                'dependencies' => [ModuleCode::Menu->value],
                'active' => true,
                'sort_order' => 1,
            ]
        );

        CorporationModule::firstOrCreate(
            [
                'corporation_id' => $corporation->id,
                'module_code' => $code->value,
            ],
            [
                'status' => ModuleStatus::Active,
                'started_at' => now(),
            ]
        );

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => $code->value,
            'status' => ModuleStatus::Active,
        ]);
    }
}
