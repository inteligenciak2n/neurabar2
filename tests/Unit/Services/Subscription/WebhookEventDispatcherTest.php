<?php

namespace Tests\Unit\Services\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\InvoiceStatus;
use App\Enums\ProfileEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueSubscription;
use App\Models\User;
use App\Notifications\Billing\CardPaymentRefused;
use App\Notifications\Subscription\PaymentChargebackReceived;
use App\Services\Subscription\PaymentSaasService;
use Illuminate\Support\Facades\Notification;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class WebhookEventDispatcherTest extends TestCase
{
    use RefreshAllDatabases;

    private function mockGateway(string $status, array $overrides = []): void
    {
        $this->mock(PaymentGatewayContract::class, function ($mock) use ($status, $overrides) {
            $mock->shouldReceive('handleWebhook')->once()->andReturn(array_merge([
                'gateway_payment_id' => 'pay_1',
                'status' => $status,
                'invoice_type' => 'venue',
                'invoice_id' => null,
                'amount' => 10000,
                'gateway_subscription_id' => null,
                'due_date' => null,
                'payload' => [],
            ], $overrides));
        });
    }

    private function invoice(InvoiceStatus $status = InvoiceStatus::Open): VenueInvoice
    {
        return VenueInvoice::factory()->create([
            'status' => $status,
            'is_finalized' => $status->isFinalized(),
            'total_value' => 10000,
            'gateway_payment_id' => 'pay_1',
            'period' => now()->format('Y-m'),
        ]);
    }

    public function test_confirmed_event_settles_the_invoice(): void
    {
        $invoice = $this->invoice();
        $this->mockGateway('paid', ['invoice_id' => $invoice->id]);

        $result = app(PaymentSaasService::class)
            ->handleWebhook('asaas', ['id' => 'evt_1', 'event' => 'PAYMENT_CONFIRMED']);

        $this->assertSame('paid', $result['status']);
        $this->assertDatabaseHas('venue_invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Paid->value,
            'is_finalized' => true,
        ]);
    }

    public function test_overdue_event_transitions_the_invoice(): void
    {
        $invoice = $this->invoice();
        $this->mockGateway('overdue', ['invoice_id' => $invoice->id]);

        app(PaymentSaasService::class)
            ->handleWebhook('asaas', ['id' => 'evt_2', 'event' => 'PAYMENT_OVERDUE']);

        $this->assertDatabaseHas('venue_invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Overdue->value,
        ]);
    }

    public function test_refund_over_an_open_invoice_is_rejected(): void
    {
        $invoice = $this->invoice();
        $this->mockGateway('refunded', ['invoice_id' => $invoice->id]);

        app(PaymentSaasService::class)
            ->handleWebhook('asaas', ['id' => 'evt_3', 'event' => 'PAYMENT_REFUNDED']);

        $this->assertDatabaseHas('venue_invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Open->value,
        ]);
    }

    public function test_refused_capture_notifies_the_corporation_owner(): void
    {
        Notification::fake();

        $invoice = $this->invoice();
        $this->mockGateway('failed', ['invoice_id' => $invoice->id]);

        app(PaymentSaasService::class)
            ->handleWebhook('asaas', ['id' => 'evt_4', 'event' => 'PAYMENT_CREDIT_CARD_CAPTURE_REFUSED']);

        $this->assertDatabaseHas('venue_invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Open->value,
        ]);

        Notification::assertSentTo($invoice->corporation->owner, CardPaymentRefused::class);
    }

    public function test_chargeback_disputes_the_invoice_suspends_the_subscription_and_alerts_the_backoffice(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['profile' => ProfileEnum::SuperAdmin]);

        $invoice = $this->invoice(InvoiceStatus::Paid);
        $subscription = VenueSubscription::factory()->create([
            'venue_id' => $invoice->venue_id,
            'status' => SubscriptionStatus::Active,
        ]);

        $this->mockGateway('refunded', ['invoice_id' => $invoice->id]);

        app(PaymentSaasService::class)
            ->handleWebhook('asaas', ['id' => 'evt_5', 'event' => 'PAYMENT_CHARGEBACK_REQUESTED']);

        $this->assertDatabaseHas('venue_invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Disputed->value,
        ]);

        $this->assertDatabaseHas('venue_subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Suspended->value,
        ]);

        Notification::assertSentTo($admin, PaymentChargebackReceived::class);
    }

    public function test_informational_event_is_ignored(): void
    {
        $invoice = $this->invoice();
        $this->mockGateway('pending', ['invoice_id' => $invoice->id]);

        $result = app(PaymentSaasService::class)
            ->handleWebhook('asaas', ['id' => 'evt_6', 'event' => 'PAYMENT_BANK_SLIP_VIEWED']);

        $this->assertSame('ignored', $result['status']);
        $this->assertDatabaseHas('venue_invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Open->value,
        ]);
    }

    public function test_unknown_event_is_ignored_instead_of_failing(): void
    {
        $invoice = $this->invoice();
        $this->mockGateway('paid', ['invoice_id' => $invoice->id]);

        $result = app(PaymentSaasService::class)
            ->handleWebhook('asaas', ['id' => 'evt_7', 'event' => 'PAYMENT_SOMETHING_BRAND_NEW']);

        $this->assertSame('ignored', $result['status']);
        $this->assertDatabaseHas('venue_invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Open->value,
        ]);
    }

    public function test_webhook_for_an_unknown_invoice_is_ignored_instead_of_throwing(): void
    {
        $this->mockGateway('paid', ['invoice_id' => null, 'gateway_payment_id' => 'pay_unknown']);

        $result = app(PaymentSaasService::class)
            ->handleWebhook('asaas', ['id' => 'evt_8', 'event' => 'PAYMENT_CONFIRMED']);

        $this->assertSame('ignored', $result['status']);
    }
}
