<?php

namespace App\Services\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\PaymentSaasMethod;
use App\Exceptions\Subscription\GatewayRequestException;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueSubscription;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class AsaasPaymentGateway implements PaymentGatewayContract
{
    private readonly string $baseUrl;

    private readonly string $accessToken;

    public function __construct()
    {
        $this->baseUrl = (string) config('services.asaas.base_url');
        $this->accessToken = (string) config('services.asaas.access_token');
    }

    public function createCustomer(array $data): string
    {
        $response = $this->client()->post('/v3/customers', [
            'name' => $data['name'],
            'cpfCnpj' => $this->onlyDigits($data['document'] ?? ''),
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'mobilePhone' => $data['mobile_phone'] ?? null,
            'externalReference' => $data['external_reference'] ?? null,
        ]);

        return (string) $this->handle($response)['id'];
    }

    public function saveCard(string $customerId, array $cardData): array
    {
        $response = $this->client()->post('/v3/creditCard/tokenizeCreditCard', [
            'customer' => $customerId,
            'creditCard' => $this->creditCardPayload($cardData),
            'creditCardHolderInfo' => $this->creditCardHolderInfoPayload($cardData),
            'remoteIp' => $cardData['remote_ip'],
        ]);

        return $this->mapCreditCardResponse($this->handle($response));
    }

    public function createSubscription(CorporationSubscription|VenueSubscription $subscription, array $data): array
    {
        $payload = [
            'customer' => $data['gateway_customer_id'],
            'billingType' => $this->mapBillingType($data['billing_type'] ?? PaymentSaasMethod::CreditCard->value),
            'value' => (float) $data['value'],
            'nextDueDate' => $data['next_due_date'],
            'cycle' => strtoupper($data['cycle'] ?? 'monthly'),
            'externalReference' => (string) $subscription->id,
            'description' => $data['description'] ?? null,
        ];

        if (! empty($data['gateway_token'])) {
            $payload['creditCardToken'] = $data['gateway_token'];
        }

        $result = $this->handle($this->client()->post('/v3/subscriptions/', $payload));

        return [
            'gateway_subscription_id' => (string) $result['id'],
            'status' => strtolower((string) ($result['status'] ?? 'active')),
            'next_due_date' => (string) ($result['nextDueDate'] ?? $data['next_due_date']),
            'payload' => $result,
        ];
    }

    public function updatePaymentValue(string $gatewayPaymentId, float $newValue): void
    {
        $this->handle($this->client()->put("/v3/payments/{$gatewayPaymentId}", [
            'value' => $newValue,
        ]));
    }

    public function updateSubscriptionCard(string $gatewaySubscriptionId, array $cardData): array
    {
        $response = $this->client()->put("/v3/subscriptions/{$gatewaySubscriptionId}/creditCard", [
            'creditCard' => $this->creditCardPayload($cardData),
            'creditCardHolderInfo' => $this->creditCardHolderInfoPayload($cardData),
            'remoteIp' => $cardData['remote_ip'],
        ]);

        $result = $this->handle($response);

        return $this->mapCreditCardResponse($result['creditCard'] ?? []);
    }

    public function cancelSubscription(string $gatewaySubscriptionId): void
    {
        $this->handle($this->client()->delete("/v3/subscriptions/{$gatewaySubscriptionId}"));
    }

    public function chargeInvoice(VenueInvoice|CorporationInvoice $invoice, array $paymentData): array
    {
        $method = PaymentSaasMethod::tryFrom($paymentData['method'] ?? '');

        if (! $method) {
            throw new InvalidArgumentException('Invalid payment method.');
        }

        return match ($method) {
            PaymentSaasMethod::CreditCard => $this->createOneOffPayment($invoice, 'CREDIT_CARD', $paymentData),
            PaymentSaasMethod::Pix => $this->processPix($invoice),
            PaymentSaasMethod::Boleto => $this->processBoleto($invoice),
        };
    }

    public function processPix(VenueInvoice|CorporationInvoice $invoice): array
    {
        return $this->createOneOffPayment($invoice, 'PIX', []);
    }

    public function processBoleto(VenueInvoice|CorporationInvoice $invoice): array
    {
        return $this->createOneOffPayment($invoice, 'BOLETO', []);
    }

    public function handleWebhook(string $gateway, array $payload): array
    {
        if ($gateway !== 'asaas') {
            throw new InvalidArgumentException("Unsupported gateway: {$gateway}");
        }

        $payment = $payload['payment'] ?? null;

        if (! $payment) {
            return [
                'gateway_payment_id' => '',
                'status' => 'ignored',
                'invoice_type' => '',
                'invoice_id' => '',
                'amount' => 0.0,
                'gateway_subscription_id' => null,
                'due_date' => null,
                'payload' => $payload,
            ];
        }

        return [
            'gateway_payment_id' => (string) $payment['id'],
            'status' => $this->mapPaymentStatus((string) ($payment['status'] ?? 'PENDING')),
            'invoice_type' => '',
            'invoice_id' => (string) ($payment['externalReference'] ?? ''),
            'amount' => (float) ($payment['value'] ?? 0),
            'gateway_subscription_id' => isset($payment['subscription']) ? (string) $payment['subscription'] : null,
            'due_date' => isset($payment['dueDate']) ? (string) $payment['dueDate'] : null,
            'payload' => $payload,
        ];
    }

    /**
     * @return array{holderName: string, number: string, expiryMonth: string, expiryYear: string, ccv: string}
     */
    private function creditCardPayload(array $cardData): array
    {
        return [
            'holderName' => $cardData['holder_name'],
            'number' => $cardData['number'],
            'expiryMonth' => (string) $cardData['expiration_month'],
            'expiryYear' => (string) $cardData['expiration_year'],
            'ccv' => $cardData['ccv'],
        ];
    }

    /**
     * @return array{name: string, email: string, cpfCnpj: string, postalCode: string, addressNumber: string, phone: string}
     */
    private function creditCardHolderInfoPayload(array $cardData): array
    {
        return [
            'name' => $cardData['holder_name'],
            'email' => $cardData['holder_email'],
            'cpfCnpj' => $this->onlyDigits($cardData['holder_document'] ?? ''),
            'postalCode' => $this->onlyDigits($cardData['holder_postal_code'] ?? ''),
            'addressNumber' => $cardData['holder_address_number'],
            'phone' => $cardData['holder_phone'],
        ];
    }

    /**
     * @return array{gateway_token: string, brand: string, last4: string}
     */
    private function mapCreditCardResponse(array $result): array
    {
        return [
            'gateway_token' => (string) ($result['creditCardToken'] ?? ''),
            'brand' => strtolower((string) ($result['creditCardBrand'] ?? 'unknown')),
            'last4' => (string) ($result['creditCardNumber'] ?? '0000'),
        ];
    }

    private function createOneOffPayment(VenueInvoice|CorporationInvoice $invoice, string $billingType, array $paymentData): array
    {
        $payload = [
            'customer' => $paymentData['gateway_customer_id'] ?? null,
            'billingType' => $billingType,
            'value' => (float) $invoice->total_value,
            'dueDate' => $invoice->due_date->toDateString(),
            'externalReference' => (string) $invoice->id,
        ];

        if (! empty($paymentData['gateway_token'])) {
            $payload['creditCardToken'] = $paymentData['gateway_token'];
        }

        $result = $this->handle($this->client()->post('/v3/payments', $payload));

        return [
            'status' => $this->mapPaymentStatus((string) ($result['status'] ?? 'PENDING')),
            'gateway_payment_id' => (string) $result['id'],
            'message' => 'Cobrança gerada com sucesso.',
            'qr_code' => $result['pixQrCode'] ?? null,
            'qr_code_image' => null,
            'expires_at' => null,
            'boleto_url' => $result['bankSlipUrl'] ?? null,
            'barcode' => null,
            'due_date' => $result['dueDate'] ?? null,
            'payload' => $result,
        ];
    }

    private function mapBillingType(string $method): string
    {
        return match ($method) {
            PaymentSaasMethod::CreditCard->value => 'CREDIT_CARD',
            PaymentSaasMethod::Pix->value => 'PIX',
            PaymentSaasMethod::Boleto->value => 'BOLETO',
            default => 'UNDEFINED',
        };
    }

    private function mapPaymentStatus(string $status): string
    {
        return match ($status) {
            'CONFIRMED', 'RECEIVED' => 'paid',
            'OVERDUE' => 'overdue',
            'REFUNDED', 'PARTIALLY_REFUNDED' => 'refunded',
            default => 'pending',
        };
    }

    private function onlyDigits(string $value): string
    {
        return preg_replace('/\D/', '', $value) ?? '';
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders(['access_token' => $this->accessToken])
            ->acceptJson();
    }

    private function handle(Response $response): array
    {
        if ($response->failed()) {
            $message = $response->json('errors.0.description') ?? 'Erro ao comunicar com o Asaas.';

            throw new GatewayRequestException($message);
        }

        return $response->json() ?? [];
    }
}
