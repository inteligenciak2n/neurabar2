<?php

namespace App\Services\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\GatewayEvent;
use App\Enums\PaymentSaasMethod;
use App\Exceptions\Subscription\GatewayRequestException;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueSubscription;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

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

        $card = $this->mapCreditCardResponse($this->handle($response));

        Log::info('asaas.card_tokenized', [
            'gateway_customer_id' => $customerId,
            'brand' => $card['brand'],
        ]);

        return $card;
    }

    public function createSubscription(CorporationSubscription|VenueSubscription $subscription, array $data): array
    {
        $payload = [
            'customer' => $data['gateway_customer_id'],
            'billingType' => $this->mapBillingType($data['billing_type'] ?? PaymentSaasMethod::CreditCard->value),
            'value' => (float) $data['value'],
            'nextDueDate' => $data['next_due_date'],
            'cycle' => strtoupper($data['cycle'] ?? 'monthly'),
            'externalReference' => 'subscription:'.$subscription->id,
            'description' => $data['description'] ?? null,
        ];

        if (! empty($data['gateway_token'])) {
            $payload['creditCardToken'] = $data['gateway_token'];
        }

        $result = $this->handle($this->client()->post('/v3/subscriptions/', $payload));

        Log::info('asaas.subscription_created', [
            'gateway_subscription_id' => $result['id'] ?? null,
            'subscription_id' => $subscription->id,
            'value' => $payload['value'],
            'cycle' => $payload['cycle'],
        ]);

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
        $response = $this->client()->delete("/v3/subscriptions/{$gatewaySubscriptionId}");

        if ($response->status() === 404) {
            return;
        }

        $this->handle($response);
    }

    public function fetchPaymentStatus(string $gatewayPaymentId): ?string
    {
        $response = $this->readClient()->get("/v3/payments/{$gatewayPaymentId}");

        if ($response->status() === 404) {
            return null;
        }

        $result = $this->handle($response);

        return $this->mapPaymentStatus((string) ($result['status'] ?? ''));
    }

    public function chargeInvoice(VenueInvoice|CorporationInvoice $invoice, array $paymentData): array
    {
        $method = PaymentSaasMethod::tryFrom($paymentData['method'] ?? '');

        if (! $method) {
            throw new InvalidArgumentException('Invalid payment method.');
        }

        return match ($method) {
            PaymentSaasMethod::CreditCard => $this->createOneOffPayment($invoice, 'CREDIT_CARD', $paymentData),
            PaymentSaasMethod::Pix => $this->processPix($invoice, $paymentData),
            PaymentSaasMethod::Boleto => $this->processBoleto($invoice, $paymentData),
        };
    }

    public function processPix(VenueInvoice|CorporationInvoice $invoice, array $paymentData = []): array
    {
        return $this->createOneOffPayment($invoice, 'PIX', $paymentData);
    }

    public function processBoleto(VenueInvoice|CorporationInvoice $invoice, array $paymentData = []): array
    {
        return $this->createOneOffPayment($invoice, 'BOLETO', $paymentData);
    }

    public function handleWebhook(string $gateway, array $payload): array
    {
        if ($gateway !== 'asaas') {
            throw new InvalidArgumentException("Unsupported gateway: {$gateway}");
        }

        $payment = $payload['payment'] ?? null;

        if (! $payment) {
            return [
                'event' => (string) ($payload['event'] ?? ''),
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

        $reference = $this->parseExternalReference((string) ($payment['externalReference'] ?? ''));

        return [
            'event' => (string) ($payload['event'] ?? ''),
            'gateway_payment_id' => (string) $payment['id'],
            'status' => $this->mapWebhookStatus((string) ($payload['event'] ?? ''), (string) ($payment['status'] ?? 'PENDING')),
            'invoice_type' => $reference['type'],
            'invoice_id' => $reference['id'],
            'amount' => (float) ($payment['value'] ?? 0),
            'gateway_subscription_id' => isset($payment['subscription']) ? (string) $payment['subscription'] : null,
            'due_date' => isset($payment['dueDate']) ? (string) $payment['dueDate'] : null,
            'payload' => $payload,
        ];
    }

    /**
     * `externalReference` used to carry a bare uuid, which forced the caller to
     * guess whether it pointed at a venue or a corporation invoice — and to
     * risk matching an unrelated row. New charges are tagged with the type and
     * with a per-attempt token, so a retried charge never collides with the
     * previous attempt of the same invoice.
     *
     * @return array{type: string, id: string}
     */
    private function parseExternalReference(string $reference): array
    {
        if ($reference === '') {
            return ['type' => '', 'id' => ''];
        }

        $segments = explode(':', $reference);

        if (count($segments) >= 2 && in_array($segments[0], ['venue', 'corporation', 'subscription'], true)) {
            return ['type' => $segments[0], 'id' => $segments[1]];
        }

        // Cobranças criadas antes da referência tipada.
        return ['type' => '', 'id' => $reference];
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
        // Cada tentativa recebe uma referência própria: é ela que permite
        // descobrir, depois de um timeout, se a cobrança chegou a existir sem
        // confundi-la com a tentativa anterior da mesma fatura.
        $externalReference = $this->externalReferenceFor($invoice, (string) Str::uuid());

        $payload = [
            'customer' => $paymentData['gateway_customer_id'] ?? null,
            'billingType' => $billingType,
            'value' => (float) $invoice->total_value,
            'dueDate' => $invoice->due_date->toDateString(),
            'externalReference' => $externalReference,
        ];

        if (! empty($paymentData['gateway_token'])) {
            $payload['creditCardToken'] = $paymentData['gateway_token'];
        }

        try {
            $result = $this->handle($this->client()->post('/v3/payments', $payload));
        } catch (ConnectionException $exception) {
            $result = $this->recoverPaymentByExternalReference($externalReference, $exception);
        }

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

    /**
     * O POST de cobrança não pode ser repetido às cegas — o Asaas não expõe
     * cabeçalho de idempotência, então uma segunda chamada geraria um segundo
     * débito. Quando a conexão cai sem resposta, consultamos a referência da
     * própria tentativa para descobrir se a cobrança foi de fato criada.
     *
     * @throws GatewayRequestException
     */
    private function recoverPaymentByExternalReference(string $externalReference, ConnectionException $exception): array
    {
        Log::warning('asaas.payment_request_unconfirmed', [
            'external_reference' => $externalReference,
            'reason' => $exception->getMessage(),
        ]);

        try {
            $existing = $this->handle(
                $this->readClient()->get('/v3/payments', ['externalReference' => $externalReference, 'limit' => 1])
            )['data'][0] ?? null;
        } catch (ConnectionException) {
            $existing = null;
        }

        if ($existing === null) {
            throw new GatewayRequestException(
                'Não foi possível confirmar a criação da cobrança no gateway: '.$exception->getMessage()
            );
        }

        Log::info('asaas.payment_recovered', [
            'external_reference' => $externalReference,
            'gateway_payment_id' => $existing['id'] ?? null,
        ]);

        return $existing;
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

    /**
     * Some events are decisive even though the payment row still reports its
     * previous status — a refused capture keeps the payment `PENDING`.
     */
    private function mapWebhookStatus(string $event, string $paymentStatus): string
    {
        $fromEvent = match (GatewayEvent::tryFrom($event)) {
            GatewayEvent::PaymentCreditCardCaptureRefused,
            GatewayEvent::PaymentReprovedByRiskAnalysis => 'failed',
            default => null,
        };

        return $fromEvent ?? $this->mapPaymentStatus($paymentStatus);
    }

    private function mapPaymentStatus(string $status): string
    {
        return match ($status) {
            'CONFIRMED', 'RECEIVED', 'RECEIVED_IN_CASH' => 'paid',
            'OVERDUE' => 'overdue',
            'REFUNDED', 'PARTIALLY_REFUNDED' => 'refunded',
            'PENDING', 'AWAITING_RISK_ANALYSIS', 'APPROVED_BY_RISK_ANALYSIS',
            'REFUND_REQUESTED', 'REFUND_IN_PROGRESS',
            'CHARGEBACK_REQUESTED', 'CHARGEBACK_DISPUTE', 'AWAITING_CHARGEBACK_REVERSAL',
            'DUNNING_REQUESTED', 'DUNNING_RECEIVED', 'AWAITING_CHARGEBACK' => 'pending',
            default => $this->unknownPaymentStatus($status),
        };
    }

    private function unknownPaymentStatus(string $status): string
    {
        Log::warning('asaas.unknown_payment_status', ['status' => $status]);

        return 'pending';
    }

    private function onlyDigits(string $value): string
    {
        return preg_replace('/\D/', '', $value) ?? '';
    }

    private function externalReferenceFor(VenueInvoice|CorporationInvoice $invoice, string $attemptToken): string
    {
        $type = $invoice instanceof VenueInvoice ? 'venue' : 'corporation';

        return $type.':'.$invoice->id.':'.$attemptToken;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders(['access_token' => $this->accessToken])
            ->acceptJson()
            // O Asaas recomenda timeout mínimo de 60s: a tokenização e a captura
            // passam por antifraude e podem demorar bem mais que uma chamada REST comum.
            ->timeout(60)
            ->connectTimeout(5);
    }

    /**
     * Cliente para leituras idempotentes.
     *
     * POST e PUT nunca são repetidos automaticamente: o Asaas não expõe
     * cabeçalho de idempotência, então uma retentativa cega criaria uma segunda
     * cobrança para o mesmo cliente. Escritas se recuperam por
     * `externalReference` (ver `recoverPaymentByExternalReference`).
     */
    private function readClient(): PendingRequest
    {
        return $this->client()->retry(
            times: 3,
            sleepMilliseconds: 200,
            when: function (Throwable $exception): bool {
                if ($exception instanceof ConnectionException) {
                    return true;
                }

                return $exception instanceof RequestException
                    && $exception->response->serverError();
            },
            throw: false,
        );
    }

    private function handle(Response $response): array
    {
        if ($response->failed()) {
            $message = $response->json('errors.0.description') ?? 'Erro ao comunicar com o Asaas.';
            $code = $response->json('errors.0.code');

            Log::error('asaas.request_failed', [
                'status' => $response->status(),
                'code' => $code,
                'description' => $message,
            ]);

            throw new GatewayRequestException($message, $code === null ? null : (string) $code);
        }

        return $response->json() ?? [];
    }
}
