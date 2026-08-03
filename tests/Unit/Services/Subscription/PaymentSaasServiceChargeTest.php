<?php

namespace Tests\Unit\Services\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentSaasMethod;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\UserPaymentMethod;
use App\Models\Tenant\VenueInvoice;
use App\Models\User;
use App\Services\Subscription\PaymentSaasService;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class PaymentSaasServiceChargeTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_charge_resolves_and_forwards_gateway_customer_id_for_pix(): void
    {
        $user = User::factory()->create();

        $invoice = VenueInvoice::factory()->create([
            'status' => InvoiceStatus::Open,
            'total_value' => 100,
            'due_date' => now()->addDays(5),
            'period' => now()->format('Y-m'),
        ]);

        $this->mock(PaymentGatewayContract::class, function ($mock) {
            $mock->shouldReceive('createCustomer')->once()->andReturn('cus_forward_1');
            $mock->shouldReceive('processPix')
                ->once()
                ->withArgs(fn ($chargedInvoice, array $paymentData) => ($paymentData['gateway_customer_id'] ?? null) === 'cus_forward_1')
                ->andReturn([
                    'status' => 'pending',
                    'gateway_payment_id' => 'pay_forward_1',
                    'message' => 'PIX gerado.',
                    'payload' => [],
                ]);
        });

        $service = app(PaymentSaasService::class);

        $result = $service->charge($invoice, ['method' => PaymentSaasMethod::Pix->value], $user);

        $this->assertSame('pending', $result['status']);
        $this->assertDatabaseHas('gateway_customers', [
            'owner_type' => Corporation::class,
            'owner_id' => $invoice->venue->corporation_id,
            'customer_id' => 'cus_forward_1',
        ]);
    }

    public function test_charge_reuses_document_from_existing_payment_method(): void
    {
        $user = User::factory()->create();

        UserPaymentMethod::factory()->create([
            'user_id' => $user->id,
            'holder_document' => '11122233344',
        ]);

        $invoice = VenueInvoice::factory()->create([
            'status' => InvoiceStatus::Open,
            'total_value' => 100,
            'due_date' => now()->addDays(5),
            'period' => now()->format('Y-m'),
        ]);

        $this->mock(PaymentGatewayContract::class, function ($mock) {
            $mock->shouldReceive('createCustomer')
                ->once()
                ->withArgs(fn (array $data) => ($data['document'] ?? null) === '11122233344')
                ->andReturn('cus_forward_2');
            $mock->shouldReceive('processBoleto')->once()->andReturn([
                'status' => 'pending',
                'gateway_payment_id' => 'pay_forward_2',
                'message' => 'Boleto gerado.',
                'payload' => [],
            ]);
        });

        $service = app(PaymentSaasService::class);
        $service->charge($invoice, ['method' => PaymentSaasMethod::Boleto->value], $user);
    }
}
