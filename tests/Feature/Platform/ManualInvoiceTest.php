<?php

namespace Tests\Feature\Platform;

use App\Enums\InvoiceStatus;
use App\Enums\ProfileEnum;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\Venue;
use Database\Seeders\PlanCatalogsSeeder;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ManualInvoiceTest extends TestCase
{
    use RefreshAllDatabases;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanCatalogsSeeder::class);
    }

    public function test_finance_can_create_manual_corporation_invoice(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::Finance);

        $corporation = Corporation::factory()->create();
        PlanCatalog::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => PlanCatalog::first()->id,
        ]);

        $this->post(route('platform.corporations.invoices.store', $corporation->id), [
            'invoiceable_type' => 'corporation',
            'invoiceable_id' => $corporation->id,
            'period' => now()->format('Y-m'),
            'due_date' => today()->addDays(7)->toDateString(),
            'base_value' => 100,
            'modules_value' => 20,
            'metered_value' => 5,
            'dedicated_surcharge' => 0,
            'discount_value' => 10,
        ])->assertRedirect();

        $this->assertDatabaseHas('corporation_invoices', [
            'corporation_id' => $corporation->id,
            'base_value' => 100,
            'total_value' => 115,
            'status' => InvoiceStatus::Open->value,
        ]);
    }

    public function test_finance_can_create_manual_venue_invoice(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::Finance);

        $corporation = Corporation::factory()->create();
        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        PlanCatalog::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => PlanCatalog::first()->id,
        ]);

        $this->post(route('platform.corporations.invoices.store', $corporation->id), [
            'invoiceable_type' => 'venue',
            'invoiceable_id' => $venue->id,
            'period' => now()->format('Y-m'),
            'due_date' => today()->addDays(7)->toDateString(),
            'base_value' => 50,
        ])->assertRedirect();

        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venue->id,
            'base_value' => 50,
            'total_value' => 50,
        ]);
    }

    public function test_finance_can_change_invoice_status(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::Finance);

        $corporation = Corporation::factory()->create();
        $invoice = CorporationInvoice::factory()->create([
            'corporation_id' => $corporation->id,
            'status' => InvoiceStatus::Open,
        ]);

        $this->put(route('platform.corporations.invoices.status', [$corporation->id, $invoice->id]), [
            'status' => 'paid',
        ])->assertRedirect();

        $this->assertDatabaseHas('corporation_invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Paid->value,
            'is_finalized' => true,
        ]);
    }
}
