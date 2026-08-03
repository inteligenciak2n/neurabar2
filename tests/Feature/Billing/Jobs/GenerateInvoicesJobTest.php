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
use App\Models\Tenant\CorporationDiscount;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class GenerateInvoicesJobTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_generates_venue_invoice_for_active_subscription(): void
    {
        $venue = $this->createActiveVenue(baseValue: 9990);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'status' => InvoiceStatus::Open->value,
            'base_value' => 9990,
            'total_value' => 9990,
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

        $venue = $this->createVenueForCorporation($corporation, baseValue: 5000);

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

        $venueA = $this->createVenueForCorporation($corporation, baseValue: 5000);
        $venueB = $this->createVenueForCorporation($corporation, baseValue: 5000);
        $this->enableModule($corporation, $venueA, ModuleCode::Kds, 4990);
        $this->enableModule($corporation, $venueB, ModuleCode::Kds, 4990);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $corporationInvoice = CorporationInvoice::query()
            ->where('corporation_id', $corporation->id)
            ->where('period', '2026-07')
            ->first();

        $this->assertNotNull($corporationInvoice);
        $this->assertSame(19980, (int) $corporationInvoice->total_value);

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
        $venue = $this->createActiveVenue(baseValue: 9990);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);
        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseCount('venue_invoices', 1);
    }

    public function test_does_not_generate_for_finalized_venue_invoice(): void
    {
        $venue = $this->createActiveVenue(baseValue: 9990);
        VenueInvoice::factory()->create([
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'is_finalized' => true,
            'status' => InvoiceStatus::Paid,
            'base_value' => 9990,
            'modules_value' => 0,
            'metered_value' => 0,
            'total_value' => 9990,
        ]);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseCount('venue_invoices', 1);
        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'base_value' => 9990,
            'modules_value' => 0,
            'metered_value' => 0,
            'total_value' => 9990,
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
        $venue = $this->createVenueForCorporation($corporation, baseValue: 5000);
        $this->enableModule($corporation, $venue, ModuleCode::Kds, 4990);

        CorporationInvoice::factory()->create([
            'corporation_id' => $corporation->id,
            'period' => '2026-07',
            'is_finalized' => true,
            'status' => InvoiceStatus::Paid,
            'base_value' => 5000,
            'modules_value' => 4990,
            'metered_value' => 0,
            'total_value' => 9990,
        ]);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseHas('corporation_invoices', [
            'corporation_id' => $corporation->id,
            'period' => '2026-07',
            'base_value' => 5000,
            'modules_value' => 4990,
            'metered_value' => 0,
            'total_value' => 9990,
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
        $venue = $this->createVenueForCorporation($corporation, baseValue: 5000);
        $this->enableModule($corporation, $venue, ModuleCode::Kds, 4990);

        VenueInvoice::factory()->create([
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'is_finalized' => true,
            'status' => InvoiceStatus::Paid,
            'base_value' => 5000,
            'modules_value' => 4990,
            'metered_value' => 0,
            'total_value' => 9990,
        ]);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'base_value' => 5000,
            'modules_value' => 4990,
            'metered_value' => 0,
            'total_value' => 9990,
        ]);
    }

    public function test_sets_due_date_based_on_billing_day(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        $venue = $this->createActiveVenue(baseValue: 9990, billingDay: 15);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'due_date' => '2026-07-15',
        ]);

        Carbon::setTestNow();
    }

    public function test_due_date_respects_minimum_lead_time_when_billing_day_already_passed(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');

        $venue = $this->createActiveVenue(baseValue: 9990, billingDay: 1);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $invoice = VenueInvoice::where('venue_id', $venue->id)->where('period', '2026-07')->firstOrFail();

        // Com billing_day = 1 a fatura nascia vencida no mesmo dia da geração.
        $this->assertSame('2026-08-01', $invoice->due_date->toDateString());

        Carbon::setTestNow();
    }

    public function test_skips_corporation_subscription_billed_by_gateway(): void
    {
        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Active,
            'billing_day' => 10,
            'gateway' => 'asaas',
            'gateway_customer_id' => 'cus_123',
            'gateway_subscription_id' => 'sub_123',
        ]);

        $this->createVenueForCorporation($corporation, baseValue: 5000);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseCount('corporation_invoices', 0);
        $this->assertDatabaseCount('venue_invoices', 0);
    }

    public function test_skips_venue_subscription_billed_by_gateway(): void
    {
        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Active,
            'billing_day' => 10,
        ]);

        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $corporation->subscription->id,
            'base_value' => 9990,
            'total_value' => 9990,
            'status' => SubscriptionStatus::Active,
            'gateway' => 'asaas',
            'gateway_customer_id' => 'cus_123',
            'gateway_subscription_id' => 'sub_123',
        ]);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseCount('venue_invoices', 0);
    }

    public function test_does_not_generate_invoice_for_inactive_venue(): void
    {
        $venue = $this->createActiveVenue(baseValue: 9990);
        $venue->update(['active' => false]);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseCount('venue_invoices', 0);
    }

    public function test_does_not_generate_invoice_when_period_is_fully_covered_by_trial(): void
    {
        $venue = $this->createActiveVenue(baseValue: 9990);
        $venue->subscription->update([
            'started_at' => '2026-06-01',
            'trial_ends_at' => '2026-08-10',
        ]);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseCount('venue_invoices', 0);
    }

    public function test_prorates_invoice_when_trial_ends_mid_period(): void
    {
        $venue = $this->createActiveVenue(baseValue: 9990);
        $venue->subscription->update([
            'started_at' => '2026-06-01',
            'trial_ends_at' => '2026-07-15',
        ]);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $invoice = VenueInvoice::where('venue_id', $venue->id)->where('period', '2026-07')->firstOrFail();

        // 16 dos 31 dias de julho ficam fora do trial.
        $this->assertSame((int) round(9990 * 16 / 31), (int) $invoice->base_value);
        $this->assertSame((int) $invoice->base_value, (int) $invoice->total_value);
    }

    public function test_discount_stops_being_applied_after_max_months_in_per_venue_mode(): void
    {
        $venue = $this->createActiveVenue(baseValue: 10000);

        CorporationDiscount::create([
            'corporation_id' => $venue->corporation_id,
            'type' => 'percentage',
            'value' => 1000,
            'valid_from' => '2026-04-01',
            'valid_until' => null,
            'max_months' => 3,
            'is_active' => true,
        ]);

        // O desconto já foi consumido nos três períodos anteriores, gravado nas
        // faturas das venues (modo per_venue).
        foreach (['2026-04', '2026-05', '2026-06'] as $period) {
            VenueInvoice::factory()->create([
                'venue_id' => $venue->id,
                'period' => $period,
                'discount_value' => 1000,
                'total_value' => 9000,
            ]);
        }

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'discount_value' => 0,
            'total_value' => 10000,
        ]);
    }

    public function test_discount_is_applied_while_under_max_months_in_per_venue_mode(): void
    {
        $venue = $this->createActiveVenue(baseValue: 10000);

        CorporationDiscount::create([
            'corporation_id' => $venue->corporation_id,
            'type' => 'percentage',
            'value' => 1000,
            'valid_from' => '2026-04-01',
            'valid_until' => null,
            'max_months' => 3,
            'is_active' => true,
        ]);

        foreach (['2026-05', '2026-06'] as $period) {
            VenueInvoice::factory()->create([
                'venue_id' => $venue->id,
                'period' => $period,
                'discount_value' => 1000,
                'total_value' => 9000,
            ]);
        }

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venue->id,
            'period' => '2026-07',
            'discount_value' => 1000,
            'total_value' => 9000,
        ]);
    }

    public function test_unified_mode_links_venue_invoices_to_the_corporation_invoice(): void
    {
        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Active,
            'billing_day' => 10,
        ]);

        $venue = $this->createVenueForCorporation($corporation, baseValue: 5000);

        (new GenerateInvoicesJob('2026-07'))->handle(new SubscriptionCalculator);

        $corporationInvoice = CorporationInvoice::where('corporation_id', $corporation->id)->firstOrFail();
        $venueInvoice = VenueInvoice::where('venue_id', $venue->id)->firstOrFail();

        // A fatura da venue é apenas o detalhamento: quem paga é a corporation.
        $this->assertSame($corporationInvoice->id, $venueInvoice->corporation_invoice_id);
    }

    private function createActiveVenue(int $baseValue, int $billingDay = 1): Venue
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
            'trial_ends_at' => null,
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
            'trial_ends_at' => null,
        ]);

        return $venue->fresh();
    }

    private function enableModule(Corporation $corporation, Venue $venue, ModuleCode $code, int $price): void
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
                'started_at' => '2026-01-01',
            ]
        );

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => $code->value,
            'status' => ModuleStatus::Active,
            'started_at' => '2026-01-01',
        ]);
    }
}
