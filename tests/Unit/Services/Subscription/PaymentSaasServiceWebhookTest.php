<?php

namespace Tests\Unit\Services\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
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
}
