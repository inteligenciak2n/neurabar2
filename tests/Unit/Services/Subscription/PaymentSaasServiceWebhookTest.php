<?php

namespace Tests\Unit\Services\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\BillingMode;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueSubscription;
use App\Services\Subscription\PaymentSaasService;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class PaymentSaasServiceWebhookTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_handle_webhook_mirrors_invoice_and_syncs_variable_value_for_new_subscription_payment(): void
    {
        $venueSubscription = VenueSubscription::factory()->create([
            'gateway' => 'asaas',
            'gateway_subscription_id' => 'sub_asaas_123',
            'base_value' => 150,
        ]);

        $this->mock(PaymentGatewayContract::class, function ($mock) {
            $mock->shouldReceive('handleWebhook')->once()->andReturn([
                'gateway_payment_id' => 'pay_asaas_1',
                'status' => 'pending',
                'invoice_type' => '',
                'invoice_id' => '',
                'amount' => 10.0,
                'gateway_subscription_id' => 'sub_asaas_123',
                'due_date' => now()->addDays(5)->toDateString(),
                'payload' => [],
            ]);
            $mock->shouldReceive('updatePaymentValue')->once()->with('pay_asaas_1', 150.0);
        });

        $service = app(PaymentSaasService::class);

        $result = $service->handleWebhook('asaas', ['id' => 'evt_1', 'event' => 'PAYMENT_CREATED']);

        $this->assertSame('pending', $result['status']);
        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venueSubscription->venue_id,
            'venue_subscription_id' => $venueSubscription->id,
            'gateway_payment_id' => 'pay_asaas_1',
            'total_value' => 150.00,
            'status' => 'open',
        ]);
    }

    public function test_handle_webhook_does_not_adjust_value_when_amount_already_matches(): void
    {
        $venueSubscription = VenueSubscription::factory()->create([
            'gateway' => 'asaas',
            'gateway_subscription_id' => 'sub_asaas_456',
            'base_value' => 80,
        ]);

        $this->mock(PaymentGatewayContract::class, function ($mock) {
            $mock->shouldReceive('handleWebhook')->once()->andReturn([
                'gateway_payment_id' => 'pay_asaas_2',
                'status' => 'pending',
                'invoice_type' => '',
                'invoice_id' => '',
                'amount' => 80.0,
                'gateway_subscription_id' => 'sub_asaas_456',
                'due_date' => now()->addDays(5)->toDateString(),
                'payload' => [],
            ]);
            $mock->shouldNotReceive('updatePaymentValue');
        });

        $service = app(PaymentSaasService::class);
        $service->handleWebhook('asaas', ['id' => 'evt_2', 'event' => 'PAYMENT_CREATED']);

        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venueSubscription->venue_id,
            'gateway_payment_id' => 'pay_asaas_2',
            'total_value' => 80.00,
        ]);
    }

    public function test_handle_webhook_updates_status_of_already_mirrored_invoice_without_recalculating_value(): void
    {
        $venueSubscription = VenueSubscription::factory()->create([
            'gateway' => 'asaas',
            'gateway_subscription_id' => 'sub_asaas_789',
            'base_value' => 60,
        ]);

        VenueInvoice::factory()->create([
            'venue_id' => $venueSubscription->venue_id,
            'venue_subscription_id' => $venueSubscription->id,
            'gateway_payment_id' => 'pay_asaas_3',
            'total_value' => 60,
            'due_date' => now()->addDays(5),
            'period' => now()->addDays(5)->format('Y-m'),
        ]);

        $this->mock(PaymentGatewayContract::class, function ($mock) {
            $mock->shouldReceive('handleWebhook')->once()->andReturn([
                'gateway_payment_id' => 'pay_asaas_3',
                'status' => 'paid',
                'invoice_type' => '',
                'invoice_id' => '',
                'amount' => 60.0,
                'gateway_subscription_id' => 'sub_asaas_789',
                'due_date' => now()->addDays(5)->toDateString(),
                'payload' => [],
            ]);
            $mock->shouldNotReceive('updatePaymentValue');
        });

        $service = app(PaymentSaasService::class);
        $service->handleWebhook('asaas', ['id' => 'evt_3', 'event' => 'PAYMENT_CONFIRMED']);

        $this->assertDatabaseHas('venue_invoices', [
            'gateway_payment_id' => 'pay_asaas_3',
            'status' => 'paid',
        ]);
        $this->assertDatabaseCount('venue_invoices', 1);
    }

    public function test_handle_webhook_does_not_call_update_payment_value_when_status_is_not_pending(): void
    {
        $venueSubscription = VenueSubscription::factory()->create([
            'gateway' => 'asaas',
            'gateway_subscription_id' => 'sub_asaas_999',
            'base_value' => 200,
        ]);

        $this->mock(PaymentGatewayContract::class, function ($mock) {
            $mock->shouldReceive('handleWebhook')->once()->andReturn([
                'gateway_payment_id' => 'pay_asaas_9',
                'status' => 'paid',
                'invoice_type' => '',
                'invoice_id' => '',
                'amount' => 150.0,
                'gateway_subscription_id' => 'sub_asaas_999',
                'due_date' => now()->addDays(5)->toDateString(),
                'payload' => [],
            ]);
            $mock->shouldNotReceive('updatePaymentValue');
        });

        $service = app(PaymentSaasService::class);
        $service->handleWebhook('asaas', ['id' => 'evt_9', 'event' => 'PAYMENT_CONFIRMED']);

        $this->assertDatabaseHas('venue_invoices', [
            'venue_id' => $venueSubscription->venue_id,
            'gateway_payment_id' => 'pay_asaas_9',
            'status' => 'paid',
            'total_value' => 150.00,
        ]);
    }

    public function test_handle_webhook_mirrors_corporation_invoice_with_full_breakdown(): void
    {
        $corporation = Corporation::factory()->create();
        $subscription = CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'gateway' => 'asaas',
            'gateway_subscription_id' => 'sub_corp_1',
        ]);

        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $subscription->id,
            'base_value' => 120,
        ]);

        $this->mock(PaymentGatewayContract::class, function ($mock) {
            $mock->shouldReceive('handleWebhook')->once()->andReturn([
                'gateway_payment_id' => 'pay_corp_1',
                'status' => 'pending',
                'invoice_type' => '',
                'invoice_id' => '',
                'amount' => 50.0,
                'gateway_subscription_id' => 'sub_corp_1',
                'due_date' => now()->addDays(5)->toDateString(),
                'payload' => [],
            ]);
            $mock->shouldReceive('updatePaymentValue')->once()->with('pay_corp_1', 120.0);
        });

        $service = app(PaymentSaasService::class);
        $service->handleWebhook('asaas', ['id' => 'evt_corp_1', 'event' => 'PAYMENT_CREATED']);

        $this->assertDatabaseHas('corporation_invoices', [
            'corporation_id' => $corporation->id,
            'gateway_payment_id' => 'pay_corp_1',
            'base_value' => 120.00,
            'total_value' => 120.00,
        ]);
    }
}
