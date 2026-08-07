<?php

namespace Tests\Feature\Billing\Jobs;

use App\Enums\InvoiceStatus;
use App\Jobs\Billing\RetryOverdueInvoicesJob;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\UserPaymentMethod;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueSubscription;
use App\Models\User;
use App\Notifications\Billing\InvoiceOverdue;
use App\Services\Subscription\PaymentSaasService;
use Illuminate\Support\Facades\Notification;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class RetryOverdueInvoicesJobTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_it_retries_the_charge_on_the_default_card(): void
    {
        [$owner, $venue] = $this->createOwnedVenue();

        UserPaymentMethod::factory()->create([
            'user_id' => $owner->id,
            'gateway_token' => 'fake_card_token',
            'is_default' => true,
        ]);

        $invoice = $this->createOverdueInvoice($venue, daysPastDue: 3);

        app(RetryOverdueInvoicesJob::class)->handle(app(PaymentSaasService::class));

        $this->assertSame(InvoiceStatus::Paid->value, $invoice->refresh()->status->value);
    }

    public function test_it_only_retries_on_the_configured_dunning_days(): void
    {
        [$owner, $venue] = $this->createOwnedVenue();

        UserPaymentMethod::factory()->create([
            'user_id' => $owner->id,
            'gateway_token' => 'fake_card_token',
            'is_default' => true,
        ]);

        $invoice = $this->createOverdueInvoice($venue, daysPastDue: 2);

        app(RetryOverdueInvoicesJob::class)->handle(app(PaymentSaasService::class));

        $this->assertSame(InvoiceStatus::Overdue->value, $invoice->refresh()->status->value);
    }

    public function test_it_notifies_the_owner_when_there_is_no_usable_card(): void
    {
        Notification::fake();

        [$owner, $venue] = $this->createOwnedVenue();
        $this->createOverdueInvoice($venue, daysPastDue: 1);

        app(RetryOverdueInvoicesJob::class)->handle(app(PaymentSaasService::class));

        Notification::assertSentTo($owner, InvoiceOverdue::class);
    }

    public function test_it_skips_invoices_billed_by_the_gateway(): void
    {
        Notification::fake();

        [$owner, $venue] = $this->createOwnedVenue();
        $venue->subscription->update(['gateway_subscription_id' => 'sub_000123']);

        $invoice = $this->createOverdueInvoice($venue, daysPastDue: 1);

        app(RetryOverdueInvoicesJob::class)->handle(app(PaymentSaasService::class));

        $this->assertSame(InvoiceStatus::Overdue->value, $invoice->refresh()->status->value);
        Notification::assertNotSentTo($owner, InvoiceOverdue::class);
    }

    /**
     * @return array{0: User, 1: Venue}
     */
    private function createOwnedVenue(): array
    {
        $owner = User::factory()->create();
        $corporation = Corporation::factory()->create(['owner_id' => $owner->id]);
        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);

        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'gateway_subscription_id' => null,
        ]);

        return [$owner, $venue->fresh()];
    }

    private function createOverdueInvoice(Venue $venue, int $daysPastDue): VenueInvoice
    {
        return VenueInvoice::factory()->create([
            'venue_id' => $venue->id,
            'status' => InvoiceStatus::Overdue,
            'is_finalized' => false,
            'total_value' => 15000,
            'due_date' => now()->subDays($daysPastDue),
            'period' => now()->subDays($daysPastDue)->format('Y-m'),
        ]);
    }
}
