<?php

namespace Tests\Unit\Services\Subscription;

use App\Exceptions\Subscription\GatewayRequestException;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Services\Subscription\AsaasPaymentGateway;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AsaasPaymentGatewayTest extends TestCase
{
    private AsaasPaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new AsaasPaymentGateway;
    }

    public function test_create_customer_sends_correct_payload_and_returns_id(): void
    {
        Http::fake([
            '*/v3/customers' => Http::response(['id' => 'cus_000001'], 200),
        ]);

        $id = $this->gateway->createCustomer([
            'name' => 'Bar do João',
            'document' => '123.456.789-01',
            'email' => 'joao@example.com',
        ]);

        $this->assertSame('cus_000001', $id);

        Http::assertSent(function ($request) {
            return str($request->url())->contains('/v3/customers')
                && $request['cpfCnpj'] === '12345678901'
                && $request['name'] === 'Bar do João';
        });
    }

    public function test_save_card_tokenizes_and_maps_response(): void
    {
        Http::fake([
            '*/v3/creditCard/tokenizeCreditCard' => Http::response([
                'creditCardToken' => 'token_abc',
                'creditCardBrand' => 'VISA',
                'creditCardNumber' => '1234',
            ], 200),
        ]);

        $result = $this->gateway->saveCard('cus_000001', [
            'holder_name' => 'Joao Silva',
            'number' => '4111111111111111',
            'expiration_month' => 12,
            'expiration_year' => 2030,
            'ccv' => '123',
            'holder_email' => 'joao@example.com',
            'holder_document' => '12345678901',
            'holder_postal_code' => '01311000',
            'holder_address_number' => '100',
            'holder_phone' => '11999999999',
            'remote_ip' => '127.0.0.1',
        ]);

        $this->assertSame([
            'gateway_token' => 'token_abc',
            'brand' => 'visa',
            'last4' => '1234',
        ], $result);
    }

    public function test_create_subscription_sends_correct_payload_and_maps_response(): void
    {
        Http::fake([
            '*/v3/subscriptions/*' => Http::response([
                'id' => 'sub_000001',
                'status' => 'ACTIVE',
                'nextDueDate' => '2026-09-02',
            ], 200),
        ]);

        $subscription = new CorporationSubscription;
        $subscription->id = 'sub-uuid-1';

        $result = $this->gateway->createSubscription($subscription, [
            'gateway_customer_id' => 'cus_000001',
            'billing_type' => 'credit_card',
            'value' => 199.9,
            'next_due_date' => '2026-09-02',
            'cycle' => 'monthly',
            'gateway_token' => 'token_abc',
        ]);

        $this->assertSame('sub_000001', $result['gateway_subscription_id']);
        $this->assertSame('active', $result['status']);
        $this->assertSame('2026-09-02', $result['next_due_date']);

        Http::assertSent(function ($request) {
            return str($request->url())->contains('/v3/subscriptions/')
                && $request['customer'] === 'cus_000001'
                && $request['billingType'] === 'CREDIT_CARD'
                && $request['creditCardToken'] === 'token_abc'
                && $request['externalReference'] === 'sub-uuid-1';
        });
    }

    public function test_update_payment_value_sends_put_request(): void
    {
        Http::fake([
            '*/v3/payments/pay_000001' => Http::response(['id' => 'pay_000001', 'value' => 250.0], 200),
        ]);

        $this->gateway->updatePaymentValue('pay_000001', 250.0);

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && str($request->url())->contains('/v3/payments/pay_000001')
                && $request['value'] === 250.0;
        });
    }

    public function test_update_subscription_card_maps_response(): void
    {
        Http::fake([
            '*/v3/subscriptions/sub_000001/creditCard' => Http::response([
                'creditCard' => [
                    'creditCardToken' => 'token_new',
                    'creditCardBrand' => 'MASTERCARD',
                    'creditCardNumber' => '4321',
                ],
            ], 200),
        ]);

        $result = $this->gateway->updateSubscriptionCard('sub_000001', [
            'holder_name' => 'Joao Silva',
            'number' => '5555555555554444',
            'expiration_month' => 6,
            'expiration_year' => 2031,
            'ccv' => '321',
            'holder_email' => 'joao@example.com',
            'holder_document' => '12345678901',
            'holder_postal_code' => '01311000',
            'holder_address_number' => '100',
            'holder_phone' => '11999999999',
            'remote_ip' => '127.0.0.1',
        ]);

        $this->assertSame([
            'gateway_token' => 'token_new',
            'brand' => 'mastercard',
            'last4' => '4321',
        ], $result);
    }

    public function test_cancel_subscription_sends_delete_request(): void
    {
        Http::fake([
            '*/v3/subscriptions/sub_000001' => Http::response(['id' => 'sub_000001', 'deleted' => true], 200),
        ]);

        $this->gateway->cancelSubscription('sub_000001');

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && str($request->url())->contains('/v3/subscriptions/sub_000001');
        });
    }

    public function test_process_pix_creates_one_off_payment(): void
    {
        Http::fake([
            '*/v3/payments' => Http::response([
                'id' => 'pay_pix_1',
                'status' => 'PENDING',
                'pixQrCode' => 'qr-code-payload',
                'dueDate' => '2026-09-10',
            ], 200),
        ]);

        $invoice = new CorporationInvoice;
        $invoice->id = 'invoice-uuid-1';
        $invoice->total_value = 100.5;
        $invoice->due_date = '2026-09-10';

        $result = $this->gateway->processPix($invoice);

        $this->assertSame('pay_pix_1', $result['gateway_payment_id']);
        $this->assertSame('pending', $result['status']);
        $this->assertSame('qr-code-payload', $result['qr_code']);

        Http::assertSent(function ($request) {
            return str($request->url())->contains('/v3/payments')
                && $request['billingType'] === 'PIX';
        });
    }

    public function test_handle_webhook_maps_payment_payload(): void
    {
        $result = $this->gateway->handleWebhook('asaas', [
            'payment' => [
                'id' => 'pay_000001',
                'status' => 'CONFIRMED',
                'externalReference' => 'invoice-uuid-1',
                'value' => 199.9,
            ],
        ]);

        $this->assertSame('pay_000001', $result['gateway_payment_id']);
        $this->assertSame('paid', $result['status']);
        $this->assertSame('invoice-uuid-1', $result['invoice_id']);
        $this->assertSame(199.9, $result['amount']);
        $this->assertNull($result['gateway_subscription_id']);
        $this->assertNull($result['due_date']);
    }

    public function test_handle_webhook_maps_subscription_generated_payment(): void
    {
        $result = $this->gateway->handleWebhook('asaas', [
            'payment' => [
                'id' => 'pay_000002',
                'status' => 'PENDING',
                'value' => 99.9,
                'dueDate' => '2026-09-10',
                'subscription' => 'sub_000001',
            ],
        ]);

        $this->assertSame('pay_000002', $result['gateway_payment_id']);
        $this->assertSame('pending', $result['status']);
        $this->assertSame('sub_000001', $result['gateway_subscription_id']);
        $this->assertSame('2026-09-10', $result['due_date']);
    }

    public function test_handle_webhook_ignores_payload_without_payment(): void
    {
        $result = $this->gateway->handleWebhook('asaas', ['event' => 'SOMETHING_ELSE']);

        $this->assertSame('ignored', $result['status']);
    }

    public function test_throws_gateway_request_exception_on_failed_response(): void
    {
        Http::fake([
            '*/v3/customers' => Http::response([
                'errors' => [
                    ['code' => 'invalid_customer', 'description' => 'CPF/CNPJ inválido.'],
                ],
            ], 400),
        ]);

        $this->expectException(GatewayRequestException::class);
        $this->expectExceptionMessage('CPF/CNPJ inválido.');

        $this->gateway->createCustomer(['name' => 'Bar do João', 'document' => '000']);
    }
}
