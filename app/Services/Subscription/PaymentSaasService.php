<?php

namespace App\Services\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentSaasMethod;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\PaymentAttempt;
use App\Models\Tenant\UserPaymentMethod;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueSubscription;
use App\Models\User;
use App\Services\Billing\SubscriptionCalculator;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class PaymentSaasService
{
    public function __construct(
        private readonly PaymentGatewayContract $gateway,
        private readonly GatewayCustomerResolver $customerResolver,
        private readonly SubscriptionCalculator $calculator,
    ) {}

    /**
     * Tokenize and save a credit card for a user.
     */
    public function saveCard(User $user, array $cardData, array $billingAddress = []): UserPaymentMethod
    {
        $gatewayName = config('subscription.payment.default', 'fake');
        $customerId = $this->customerResolver->resolve($user, $gatewayName, $cardData['holder_document'] ?? null);

        $token = $this->gateway->saveCard($customerId, $cardData);

        $method = UserPaymentMethod::create([
            'user_id' => $user->id,
            'gateway' => $gatewayName,
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
    public function charge(VenueInvoice|CorporationInvoice $invoice, array $paymentData, User $user): array
    {
        $method = PaymentSaasMethod::tryFrom($paymentData['method'] ?? '');

        if (! $method) {
            throw new InvalidArgumentException('Método de pagamento inválido.');
        }

        if ($invoice->status->isFinalized()) {
            throw new InvalidArgumentException('Esta fatura já foi finalizada.');
        }

        $paymentData['gateway_customer_id'] = $this->resolveGatewayCustomerId($user, $invoice);

        $result = match ($method) {
            PaymentSaasMethod::CreditCard => $this->chargeWithCard($invoice, $paymentData),
            PaymentSaasMethod::Pix => $this->gateway->processPix($invoice, $paymentData),
            PaymentSaasMethod::Boleto => $this->gateway->processBoleto($invoice, $paymentData),
        };

        $this->recordAttempt($invoice, $result);

        if ($result['status'] === 'paid') {
            $invoice->update([
                'status' => InvoiceStatus::Paid,
                'gateway_payment_id' => $result['gateway_payment_id'],
                'paid_at' => now(),
                'is_finalized' => true,
            ]);
        }

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

        if ($result['status'] === 'ignored') {
            return $result;
        }

        $invoice = $this->resolveInvoice($result['invoice_type'], $result['invoice_id'])
            ?? $this->resolveInvoiceByGatewayPaymentId($result['gateway_payment_id'])
            ?? $this->mirrorSubscriptionInvoice($result);

        if (! $invoice) {
            throw new InvalidArgumentException('Fatura não encontrada.');
        }

        $this->recordAttempt($invoice, $result);
        $this->updateInvoiceFromGatewayStatus($invoice, $result['status'], $result['gateway_payment_id']);

        return $result;
    }

    private function updateInvoiceFromGatewayStatus(VenueInvoice|CorporationInvoice $invoice, string $status, string $gatewayPaymentId): void
    {
        if ($status === 'paid') {
            $invoice->update([
                'status' => InvoiceStatus::Paid,
                'gateway_payment_id' => $gatewayPaymentId,
                'paid_at' => now(),
                'is_finalized' => true,
            ]);

            return;
        }

        if ($status === 'refunded') {
            $invoice->update([
                'status' => InvoiceStatus::Refunded,
                'is_finalized' => true,
            ]);
        }
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

    /**
     * Resolve the gateway customer id to link a one-off charge to, reusing
     * the acting user's stored document (from a previous card) or falling
     * back to the corporation's tax id.
     */
    private function resolveGatewayCustomerId(User $user, VenueInvoice|CorporationInvoice $invoice): string
    {
        $gatewayName = config('subscription.payment.default', 'fake');

        $document = $user->paymentMethods()->whereNotNull('holder_document')->value('holder_document')
            ?? $invoice->corporation?->tax_id;

        return $this->customerResolver->resolve($user, $gatewayName, $document);
    }

    private function recordAttempt(VenueInvoice|CorporationInvoice $invoice, array $result): void
    {
        $invoiceType = $invoice instanceof VenueInvoice ? 'venue' : 'corporation';

        PaymentAttempt::create([
            'invoice_type' => $invoiceType,
            'invoice_id' => $invoice->id,
            'gateway' => config('subscription.payment.default', 'fake'),
            'gateway_payment_id' => $result['gateway_payment_id'],
            'amount' => $result['payload']['amount'] ?? (float) $invoice->total_value,
            'status' => $result['status'],
            'payload' => $result['payload'] ?? [],
            'attempted_at' => now(),
            'succeeded_at' => $result['status'] === 'paid' ? now() : null,
            'failed_at' => in_array($result['status'], ['failed', 'expired'], true) ? now() : null,
            'failure_reason' => $result['status'] === 'failed' ? ($result['message'] ?? 'Falha no pagamento') : null,
        ]);
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

    private function resolveInvoiceByGatewayPaymentId(string $gatewayPaymentId): VenueInvoice|CorporationInvoice|null
    {
        if ($gatewayPaymentId === '') {
            return null;
        }

        return VenueInvoice::where('gateway_payment_id', $gatewayPaymentId)->first()
            ?? CorporationInvoice::where('gateway_payment_id', $gatewayPaymentId)->first();
    }

    /**
     * Mirror locally a charge that the gateway generated natively from a
     * recurring subscription (no local invoice exists yet), adjusting its
     * value to reflect the current cycle calculation (modules/usage/overage)
     * before it gets paid/confirmed.
     *
     * @param  array{gateway_payment_id: string, amount: float, gateway_subscription_id: ?string, due_date: ?string}  $result
     */
    private function mirrorSubscriptionInvoice(array $result): VenueInvoice|CorporationInvoice|null
    {
        $gatewaySubscriptionId = $result['gateway_subscription_id'] ?? null;

        if (! $gatewaySubscriptionId || $result['gateway_payment_id'] === '') {
            return null;
        }

        $dueDate = $result['due_date'] ? Carbon::parse($result['due_date']) : now();
        $period = $dueDate->format('Y-m');

        $venueSubscription = VenueSubscription::where('gateway_subscription_id', $gatewaySubscriptionId)->first();

        if ($venueSubscription) {
            return $this->mirrorVenueInvoice($venueSubscription, $result, $dueDate, $period);
        }

        $corporationSubscription = CorporationSubscription::where('gateway_subscription_id', $gatewaySubscriptionId)->first();

        if ($corporationSubscription) {
            return $this->mirrorCorporationInvoice($corporationSubscription, $result, $dueDate, $period);
        }

        return null;
    }

    private function mirrorVenueInvoice(VenueSubscription $subscription, array $result, Carbon $dueDate, string $period): ?VenueInvoice
    {
        $venue = $subscription->venue;

        if (! $venue) {
            return null;
        }

        $calculated = $this->calculator->calculateVenue($venue, $period) ?? [
            'base' => (float) $subscription->base_value,
            'modules' => (float) $subscription->modules_value,
            'metered' => (float) $subscription->metered_value,
            'dedicated_surcharge' => (float) $subscription->dedicated_surcharge,
            'total' => (float) $subscription->total_value,
        ];

        $this->syncGatewayValue($result, (float) $calculated['total']);

        $total = $result['status'] === 'pending' ? (float) $calculated['total'] : (float) $result['amount'];

        return VenueInvoice::updateOrCreate(
            ['gateway_payment_id' => $result['gateway_payment_id']],
            [
                'venue_id' => $venue->id,
                'venue_subscription_id' => $subscription->id,
                'affiliate_code_id' => $subscription->affiliate_code_id,
                'period' => $period,
                'due_date' => $dueDate->toDateString(),
                'status' => InvoiceStatus::Open,
                'base_value' => $calculated['base'],
                'modules_value' => $calculated['modules'],
                'metered_value' => $calculated['metered'],
                'dedicated_surcharge' => $calculated['dedicated_surcharge'],
                'discount_value' => 0,
                'total_value' => $total,
            ],
        );
    }

    private function mirrorCorporationInvoice(CorporationSubscription $subscription, array $result, Carbon $dueDate, string $period): ?CorporationInvoice
    {
        $corporation = $subscription->corporation;

        if (! $corporation) {
            return null;
        }

        $calculated = $this->calculator->calculateCorporation($corporation, $period);
        $breakdown = $this->aggregateCorporationBreakdown($calculated);

        $this->syncGatewayValue($result, $breakdown['total']);

        $total = $result['status'] === 'pending' ? $breakdown['total'] : (float) $result['amount'];

        return CorporationInvoice::updateOrCreate(
            ['gateway_payment_id' => $result['gateway_payment_id']],
            [
                'corporation_id' => $corporation->id,
                'corporation_subscription_id' => $subscription->id,
                'affiliate_code_id' => $subscription->affiliate_code_id,
                'period' => $period,
                'due_date' => $dueDate->toDateString(),
                'status' => InvoiceStatus::Open,
                'base_value' => $breakdown['base'],
                'modules_value' => $breakdown['modules'],
                'metered_value' => $breakdown['metered'],
                'dedicated_surcharge' => $breakdown['dedicated_surcharge'],
                'discount_value' => 0,
                'total_value' => $total,
            ],
        );
    }

    /**
     * Sum the per-venue breakdown produced by the calculator into the flat
     * shape expected by CorporationInvoice, instead of discarding it.
     *
     * @param  array{venues?: array<int|string, array<string, mixed>>, total?: float}  $calculated
     * @return array{base: float, modules: float, metered: float, dedicated_surcharge: float, total: float}
     */
    private function aggregateCorporationBreakdown(array $calculated): array
    {
        $breakdown = ['base' => 0.0, 'modules' => 0.0, 'metered' => 0.0, 'dedicated_surcharge' => 0.0];

        foreach ($calculated['venues'] ?? [] as $venueTotals) {
            $breakdown['base'] += (float) ($venueTotals['base'] ?? 0);
            $breakdown['modules'] += (float) ($venueTotals['modules'] ?? 0);
            $breakdown['metered'] += (float) ($venueTotals['metered'] ?? 0);
            $breakdown['dedicated_surcharge'] += (float) ($venueTotals['dedicated_surcharge'] ?? 0);
        }

        $breakdown['total'] = (float) ($calculated['total'] ?? 0.0);

        return $breakdown;
    }

    private function syncGatewayValue(array $result, float $expectedValue): void
    {
        if ($result['status'] !== 'pending') {
            return;
        }

        if (abs($expectedValue - $result['amount']) < 0.01) {
            return;
        }

        $this->gateway->updatePaymentValue($result['gateway_payment_id'], $expectedValue);
    }
}
