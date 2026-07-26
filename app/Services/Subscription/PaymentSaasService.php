<?php

namespace App\Services\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentSaasMethod;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\PaymentAttempt;
use App\Models\Tenant\UserPaymentMethod;
use App\Models\Tenant\VenueInvoice;
use App\Models\User;
use InvalidArgumentException;

class PaymentSaasService
{
    public function __construct(private readonly PaymentGatewayContract $gateway) {}

    /**
     * Tokenize and save a credit card for a user.
     */
    public function saveCard(User $user, array $cardData, array $billingAddress = []): UserPaymentMethod
    {
        $customerId = $this->gateway->createCustomer([
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $token = $this->gateway->saveCard($customerId, $cardData);

        $method = UserPaymentMethod::create([
            'user_id' => $user->id,
            'gateway' => config('subscription.payment.default', 'fake'),
            'gateway_token' => $token['gateway_token'],
            'brand' => $token['brand'],
            'last4' => $token['last4'],
            'holder_name' => $cardData['holder_name'],
            'holder_document' => $cardData['holder_document'] ?? null,
            'expiration_month' => $cardData['expiration_month'] ?? null,
            'expiration_year' => $cardData['expiration_year'] ?? null,
            'is_default' => ! $user->paymentMethods()->exists(),
            'billing_address_json' => $billingAddress ?: null,
        ]);

        if ($method->is_default) {
            $method->setAsDefault();
        }

        return $method;
    }

    /**
     * Charge an invoice using the chosen payment method.
     *
     * @return array{status: string, gateway_payment_id: string, message: string}
     */
    public function charge(VenueInvoice|CorporationInvoice $invoice, array $paymentData): array
    {
        $method = PaymentSaasMethod::tryFrom($paymentData['method'] ?? '');

        if (! $method) {
            throw new InvalidArgumentException('Método de pagamento inválido.');
        }

        if ($invoice->status->isFinalized()) {
            throw new InvalidArgumentException('Esta fatura já foi finalizada.');
        }

        $result = match ($method) {
            PaymentSaasMethod::CreditCard => $this->chargeWithCard($invoice, $paymentData),
            PaymentSaasMethod::Pix => $this->gateway->processPix($invoice),
            PaymentSaasMethod::Boleto => $this->gateway->processBoleto($invoice),
        };

        $this->recordAttempt($invoice, $result);

        return [
            'status' => $result['status'],
            'gateway_payment_id' => $result['gateway_payment_id'],
            'message' => $result['message'] ?? 'Pagamento processado.',
        ];
    }

    /**
     * Process a webhook payload.
     */
    public function handleWebhook(string $gateway, array $payload): array
    {
        $result = $this->gateway->handleWebhook($gateway, $payload);

        $invoice = $this->resolveInvoice($result['invoice_type'], $result['invoice_id']);

        if (! $invoice) {
            throw new InvalidArgumentException('Fatura não encontrada.');
        }

        $this->recordAttempt($invoice, $result);

        if ($result['status'] === 'paid') {
            $invoice->update([
                'status' => InvoiceStatus::Paid,
                'gateway_payment_id' => $result['gateway_payment_id'],
                'paid_at' => now(),
                'is_finalized' => true,
            ]);
        } elseif ($result['status'] === 'refunded') {
            $invoice->update([
                'status' => InvoiceStatus::Refunded,
                'is_finalized' => true,
            ]);
        }

        return $result;
    }

    private function chargeWithCard(VenueInvoice|CorporationInvoice $invoice, array $paymentData): array
    {
        $methodId = $paymentData['payment_method_id'] ?? null;

        if ($methodId) {
            $method = UserPaymentMethod::findOrFail($methodId);

            if ($method->isExpired()) {
                throw new InvalidArgumentException('Cartão expirado.');
            }

            $paymentData['gateway_token'] = $method->gateway_token;
        }

        return $this->gateway->chargeInvoice($invoice, $paymentData);
    }

    private function recordAttempt(VenueInvoice|CorporationInvoice $invoice, array $result): void
    {
        $invoiceType = $invoice instanceof VenueInvoice ? 'venue' : 'corporation';

        PaymentAttempt::updateOrCreate(
            ['gateway_payment_id' => $result['gateway_payment_id']],
            [
                'invoice_type' => $invoiceType,
                'invoice_id' => $invoice->id,
                'gateway' => config('subscription.payment.default', 'fake'),
                'amount' => $result['payload']['amount'] ?? (float) $invoice->total_value,
                'status' => $result['status'],
                'payload' => $result['payload'] ?? [],
                'attempted_at' => now(),
                'succeeded_at' => $result['status'] === 'paid' ? now() : null,
                'failed_at' => in_array($result['status'], ['failed', 'expired'], true) ? now() : null,
                'failure_reason' => $result['status'] === 'failed' ? ($result['message'] ?? 'Falha no pagamento') : null,
            ]
        );
    }

    private function resolveInvoice(string $type, ?string $id): VenueInvoice|CorporationInvoice|null
    {
        if (! $id) {
            return null;
        }

        return $type === 'corporation'
            ? CorporationInvoice::find($id)
            : VenueInvoice::find($id);
    }
}
