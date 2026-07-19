<?php

namespace Tests\Feature\Platform;

use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_platform_user_can_list_invoices(): void
    {
        $user = User::factory()->create(['profile' => 'super_admin']);
        $period = now()->format('Y-m');
        CorporationInvoice::factory()->create(['period' => $period]);
        VenueInvoice::factory()->create(['period' => $period]);

        $response = $this->actingAs($user)->get(route('platform.invoices.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Platform/Invoices/Index')
            ->has('corporationInvoices.data', 1)
            ->has('venueInvoices.data', 1)
        );
    }

    public function test_platform_user_can_filter_invoices_by_period(): void
    {
        $user = User::factory()->create(['profile' => 'super_admin']);
        CorporationInvoice::factory()->create(['period' => '2026-06']);
        CorporationInvoice::factory()->create(['period' => '2026-07']);

        $response = $this->actingAs($user)->get(route('platform.invoices.index', ['period' => '2026-07']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Platform/Invoices/Index')
            ->has('corporationInvoices.data', 1)
            ->where('filters.period', '2026-07')
        );
    }

    public function test_platform_user_can_show_corporation_invoice(): void
    {
        $user = User::factory()->create(['profile' => 'super_admin']);
        $invoice = CorporationInvoice::factory()->create();

        $response = $this->actingAs($user)->get(route('platform.invoices.show', $invoice));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Platform/Invoices/Show')
            ->where('invoice.id', $invoice->id)
        );
    }

    public function test_platform_user_can_show_venue_invoice(): void
    {
        $user = User::factory()->create(['profile' => 'super_admin']);
        $invoice = VenueInvoice::factory()->create();

        $response = $this->actingAs($user)->get(route('platform.invoices.show', $invoice));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Platform/Invoices/Show')
            ->where('invoice.id', $invoice->id)
        );
    }
}
