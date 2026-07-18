<?php

namespace Tests\Feature\Billing\Jobs;

use App\Jobs\Billing\MarkInvoicesOverdueJob;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use Carbon\Carbon;
use Tests\TestCase;

class MarkInvoicesOverdueJobTest extends TestCase
{
    public function test_it_marks_open_past_due_venue_invoices_as_overdue(): void
    {
        $venue = Venue::factory()->create();
        $invoice = VenueInvoice::factory()->create([
            'venue_id' => $venue->id,
            'venue_subscription_id' => null,
            'corporation_invoice_id' => null,
            'status' => 'open',
            'due_date' => Carbon::yesterday(),
            'is_finalized' => false,
        ]);

        (new MarkInvoicesOverdueJob)->handle();

        $this->assertDatabaseHas('venue_invoices', [
            'id' => $invoice->id,
            'status' => 'overdue',
            'is_finalized' => true,
        ]);
    }

    public function test_it_marks_open_past_due_corporation_invoices_as_overdue(): void
    {
        $subscription = CorporationSubscription::factory()->create();
        $invoice = CorporationInvoice::factory()->create([
            'corporation_id' => $subscription->corporation_id,
            'corporation_subscription_id' => $subscription->id,
            'status' => 'open',
            'due_date' => Carbon::yesterday(),
            'is_finalized' => false,
        ]);

        (new MarkInvoicesOverdueJob)->handle();

        $this->assertDatabaseHas('corporation_invoices', [
            'id' => $invoice->id,
            'status' => 'overdue',
            'is_finalized' => true,
        ]);
    }
}
