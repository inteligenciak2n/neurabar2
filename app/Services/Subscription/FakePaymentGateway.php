<?php

namespace App\Services\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\PaymentSaasMethod;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use InvalidArgumentException;

class FakePaymentGateway implements PaymentGatewayContract
{
    public function createCustomer(array $data): string
    {
        return 'fake_cus_'.md5(serialize($data));
    }

    public function saveCard(string $customerId, array $cardData): array
    {
        $number = preg_replace('/\D/', '', (string) ($cardData['number'] ?? ''));
        $last4 = substr($number, -4) ?: '0000';

        return [
            'gateway_token' => 'fake_card_'.md5($customerId.$number),
            'brand' => $this->guessBrand($number),
            'last4' => $last4,
        ];
    }

    public function chargeInvoice(VenueInvoice|CorporationInvoice $invoice, array $paymentData): array
    {
        $method = PaymentSaasMethod::tryFrom($paymentData['method'] ?? '');

        if (! $method) {
            throw new InvalidArgumentException('Invalid payment method.');
        }

        return match ($method) {
            PaymentSaasMethod::CreditCard => $this->fakeCharge($invoice, $paymentData),
            PaymentSaasMethod::Pix => $this->processPix($invoice),
            PaymentSaasMethod::Boleto => $this->processBoleto($invoice),
        };
    }

    public function processPix(VenueInvoice|CorporationInvoice $invoice): array
    {
        $gatewayPaymentId = 'fake_pix_'.md5($invoice->id.now());

        return [
            'status' => 'pending',
            'gateway_payment_id' => $gatewayPaymentId,
            'qr_code' => '00020126580014BR.GOV.BCB.PIX0136123e4567-e89b-12d3-a456-426614174000',
            'qr_code_image' => null,
            'expires_at' => now()->addHours(24)->toDateTimeString(),
            'message' => 'PIX gerado com sucesso. Escaneie o QR code para pagar.',
            'payload' => [
                'invoice_id' => $invoice->id,
                'invoice_type' => $invoice instanceof VenueInvoice ? 'venue' : 'corporation',
                'amount' => (float) $invoice->total_value,
                'gateway_payment_id' => $gatewayPaymentId,
            ],
        ];
    }

    public function processBoleto(VenueInvoice|CorporationInvoice $invoice): array
    {
        $gatewayPaymentId = 'fake_boleto_'.md5($invoice->id.now());

        return [
            'status' => 'pending',
            'gateway_payment_id' => $gatewayPaymentId,
            'boleto_url' => 'https://fake-gateway.example/boleto/'.$gatewayPaymentId,
            'barcode' => '12345678901234567890123456789012345678901234567',
            'due_date' => $invoice->due_date->toDateString(),
            'message' => 'Boleto gerado com sucesso.',
            'payload' => [
                'invoice_id' => $invoice->id,
                'invoice_type' => $invoice instanceof VenueInvoice ? 'venue' : 'corporation',
                'amount' => (float) $invoice->total_value,
                'gateway_payment_id' => $gatewayPaymentId,
            ],
        ];
    }

    public function handleWebhook(string $gateway, array $payload): array
    {
        if ($gateway !== 'fake') {
            throw new InvalidArgumentException("Unsupported gateway: {$gateway}");
        }

        $gatewayPaymentId = $payload['gateway_payment_id'] ?? ($payload['id'] ?? null);

        if (! $gatewayPaymentId) {
            throw new InvalidArgumentException('Missing gateway_payment_id in payload.');
        }

        $status = $payload['status'] ?? 'paid';
        $invoiceType = $payload['invoice_type'] ?? 'venue';
        $invoiceId = $payload['invoice_id'] ?? null;
        $amount = (float) ($payload['amount'] ?? 0);

        if (! in_array($status, ['paid', 'failed', 'refunded', 'expired'], true)) {
            $status = 'paid';
        }

        return [
            'gateway_payment_id' => (string) $gatewayPaymentId,
            'status' => $status,
            'invoice_type' => $invoiceType,
            'invoice_id' => (string) $invoiceId,
            'amount' => $amount,
            'payload' => $payload,
        ];
    }

    private function fakeCharge(VenueInvoice|CorporationInvoice $invoice, array $paymentData): array
    {
        $gatewayPaymentId = 'fake_charge_'.md5($invoice->id.now());
        $status = ($paymentData['simulate_failure'] ?? false) ? 'failed' : 'paid';

        return [
            'status' => $status,
            'gateway_payment_id' => $gatewayPaymentId,
            'message' => $status === 'paid' ? 'Pagamento aprovado.' : 'Pagamento recusado pelo gateway.',
            'payload' => [
                'invoice_id' => $invoice->id,
                'invoice_type' => $invoice instanceof VenueInvoice ? 'venue' : 'corporation',
                'amount' => (float) $invoice->total_value,
                'gateway_payment_id' => $gatewayPaymentId,
            ],
        ];
    }

    private function guessBrand(string $number): string
    {
        return match (true) {
            str_starts_with($number, '4') => 'visa',
            str_starts_with($number, '5') => 'mastercard',
            str_starts_with($number, '3') => 'amex',
            str_starts_with($number, '6') => 'discover',
            default => 'unknown',
        };
    }
}
