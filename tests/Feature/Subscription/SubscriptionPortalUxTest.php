<?php

namespace Tests\Feature\Subscription;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Tenant\PaymentAttempt;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class SubscriptionPortalUxTest extends TestCase
{
    use RefreshAllDatabases;

    private User $user;

    private Venue $venue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->venue = Venue::factory()->create();
        $this->user = $this->loginAs(UserRole::Owner, $this->venue);
    }

    public function test_subscription_index_exposes_the_trial_end_date(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.subscription.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Subscription/Index')
                ->has('subscription.trial_ends_at')
            );
    }

    public function test_invoice_show_exposes_the_pending_payment_instructions(): void
    {
        $invoice = VenueInvoice::factory()->create([
            'venue_id' => $this->venue->id,
            'status' => InvoiceStatus::Open,
            'is_finalized' => false,
        ]);

        $this->createAttempt($invoice, 'pending');

        $this->actingAs($this->user)
            ->get(route('settings.subscription.invoices.show', ['venue', $invoice->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Subscription/InvoiceShow')
                ->where('paymentInstructions.pix_code', '00020126-pix-copia-e-cola')
                ->where('paymentInstructions.boleto_url', 'https://gateway.test/boleto')
                ->missing('paymentInstructions.customer')
            );
    }

    public function test_invoice_show_hides_the_instructions_once_the_invoice_is_finalized(): void
    {
        $invoice = VenueInvoice::factory()->create([
            'venue_id' => $this->venue->id,
            'status' => InvoiceStatus::Paid,
            'is_finalized' => true,
        ]);

        $this->createAttempt($invoice, 'pending');

        $this->actingAs($this->user)
            ->get(route('settings.subscription.invoices.show', ['venue', $invoice->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('paymentInstructions', null));
    }

    private function createAttempt(VenueInvoice $invoice, string $status): void
    {
        PaymentAttempt::create([
            'invoice_type' => 'venue',
            'invoice_id' => $invoice->id,
            'gateway' => 'asaas',
            'gateway_payment_id' => 'pay_123',
            'amount' => $invoice->total_value,
            'status' => $status,
            'payload' => [
                'pixQrCode' => '00020126-pix-copia-e-cola',
                'bankSlipUrl' => 'https://gateway.test/boleto',
                'customer' => 'cus_secret',
            ],
            'attempted_at' => now(),
        ]);
    }
}
