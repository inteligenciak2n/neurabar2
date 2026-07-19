<?php

namespace Tests\Feature\Billing\Jobs;

use App\Enums\InvoiceStatus;
use App\Jobs\Billing\MarkInvoicesOverdueJob;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Notifications\Billing\InvoiceOverdue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
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
            'status' => InvoiceStatus::Open,
            'due_date' => Carbon::yesterday(),
            'is_finalized' => false,
        ]);

        (new MarkInvoicesOverdueJob)->handle();

        $this->assertDatabaseHas('venue_invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Overdue->value,
            'is_finalized' => false,
        ]);
    }

    public function test_it_sends_invoice_overdue_notification_to_owner(): void
    {
        Notification::fake();

        $venue = Venue::factory()->create();
        $invoice = VenueInvoice::factory()->create([
            'venue_id' => $venue->id,
            'status' => InvoiceStatus::Open,
            'due_date' => Carbon::yesterday(),
            'is_finalized' => false,
        ]);

        (new MarkInvoicesOverdueJob)->handle();

        Notification::assertSentTo(
            $venue->corporation->owner,
            InvoiceOverdue::class
        );
    }

    public function test_it_marks_open_past_due_corporation_invoices_as_overdue(): void
    {
        $subscription = CorporationSubscription::factory()->create();
        $invoice = CorporationInvoice::factory()->create([
            'corporation_id' => $subscription->corporation_id,
            'corporation_subscription_id' => $subscription->id,
            'status' => InvoiceStatus::Open,
            'due_date' => Carbon::yesterday(),
            'is_finalized' => false,
        ]);

        (new MarkInvoicesOverdueJob)->handle();

        $this->assertDatabaseHas('corporation_invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Overdue->value,
            'is_finalized' => false,
        ]);
    }

    public function test_it_does_not_change_already_finalized_invoices(): void
    {
        $venue = Venue::factory()->create();
        $invoice = VenueInvoice::factory()->create([
            'venue_id' => $venue->id,
            'status' => InvoiceStatus::Paid,
            'due_date' => Carbon::yesterday(),
            'is_finalized' => true,
        ]);

        (new MarkInvoicesOverdueJob)->handle();

        $this->assertDatabaseHas('venue_invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Paid->value,
        ]);
    }

    public function test_invoice_status_cast_returns_enum_instance(): void
    {
        $invoice = VenueInvoice::factory()->create([
            'status' => InvoiceStatus::Open->value,
        ]);

        $this->assertInstanceOf(InvoiceStatus::class, $invoice->fresh()->status);
        $this->assertFalse($invoice->fresh()->status->isFinalized());
    }
}
