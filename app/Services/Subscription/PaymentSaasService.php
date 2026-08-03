<?php

namespace App\Services\Subscription;

use App\Actions\Subscription\ReactivateSubscriptionAction;
use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\GatewayEvent;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentSaasMethod;
use App\Exceptions\Subscription\GatewayRequestException;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\PaymentAttempt;
use App\Models\Tenant\UserPaymentMethod;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueSubscription;
use App\Models\User;
use App\Services\Billing\SubscriptionCalculator;
use App\Services\Subscription\Webhook\WebhookContext;
use App\Services\Subscription\Webhook\WebhookEventDispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentSaasService
{
    public function __construct(
        private readonly PaymentGatewayContract $gateway,
        private readonly GatewayCustomerResolver $customerResolver,
        private readonly SubscriptionCalculator $calculator,
        private readonly ReactivateSubscriptionAction $reactivator,
        private readonly WebhookEventDispatcher $dispatcher,
    ) {}

    /**
     * Tokenize and save a credit card for a user.
     */
    public function saveCard(User $user, array $cardData, array $billingAddress = []): UserPaymentMethod
    {
        $gatewayName = config('subscription.payment.default', 'fake');
        $customerId = $this->customerResolver->resolve(
            $this->resolveUserCorporation($user),
            $user,
            $gatewayName,
            $cardData['holder_document'] ?? null,
        );

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
        // Duplo clique no botão de pagar gerava duas cobranças reais: a checagem
        // de status abaixo não protege contra concorrência sozinha.
        $lock = Cache::lock('invoice-charge:'.$invoice->getKey(), 30);

        if (! $lock->get()) {
            throw new InvalidArgumentException('Já existe um pagamento em andamento para esta fatura.');
        }

        try {
            return $this->processCharge($invoice->refresh(), $paymentData, $user);
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array{status: string, gateway_payment_id: string, message: string}
     */
    private function processCharge(VenueInvoice|CorporationInvoice $invoice, array $paymentData, User $user): array
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
            PaymentSaasMethod::CreditCard => $this->chargeWithCard($invoice, $paymentData, $user),
            PaymentSaasMethod::Pix => $this->gateway->processPix($invoice, $paymentData),
            PaymentSaasMethod::Boleto => $this->gateway->processBoleto($invoice, $paymentData),
        };

        $this->recordAttempt($invoice, $result);

        // O identificador da cobrança precisa ser gravado assim que existe.
        // Guardá-lo só no caminho "paid" fazia o webhook do PIX confirmado não
        // achar a fatura, que seguia em aberto até ser suspensa.
        if ($result['gateway_payment_id'] !== '' && $invoice->gateway_payment_id !== $result['gateway_payment_id']) {
            $invoice->update(['gateway_payment_id' => $result['gateway_payment_id']]);
        }

        if ($result['status'] === 'paid') {
            $invoice->update([
                'status' => InvoiceStatus::Paid,
                'paid_at' => now(),
                'is_finalized' => true,
            ]);

            $this->reactivator->execute($invoice);
        }

        Log::info('billing.charge.processed', [
            'invoice_type' => $invoice instanceof VenueInvoice ? 'venue' : 'corporation',
            'invoice_id' => $invoice->id,
            'method' => $method->value,
            'status' => $result['status'],
            'gateway_payment_id' => $result['gateway_payment_id'],
        ]);

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
        $event = $this->resolveEvent($payload, $result);

        if ($event === null) {
            Log::warning('gateway.webhook.unmapped_event', [
                'gateway' => $gateway,
                'event' => $payload['event'] ?? null,
                'gateway_payment_id' => $result['gateway_payment_id'] ?? '',
            ]);

            return $this->ignored($result);
        }

        if ($result['status'] === 'ignored' || $event->isInformational()) {
            Log::info('gateway.webhook.ignored', [
                'gateway' => $gateway,
                'event' => $event->value,
                'gateway_payment_id' => $result['gateway_payment_id'] ?? '',
            ]);

            return $this->ignored($result);
        }

        // Espelhar a fatura, registrar a tentativa e mudar o status precisam
        // acontecer juntos: uma falha no meio deixava a tentativa gravada sem a
        // fatura correspondente.
        return DB::connection('saas')->transaction(function () use ($gateway, $event, $result) {
            $invoice = $this->resolveInvoice($result['invoice_type'], $result['invoice_id'])
                ?? $this->resolveInvoiceByGatewayPaymentId($result['gateway_payment_id'])
                ?? $this->mirrorSubscriptionInvoice($result);

            if (! $invoice) {
                // Cobrança avulsa criada fora da plataforma (ex.: PIX gerado no
                // painel do gateway). Lançar aqui só queimava as 5 tentativas
                // do job sem nunca existir fatura para conciliar.
                Log::warning('gateway.webhook.invoice_not_found', [
                    'gateway' => $gateway,
                    'event' => $event->value,
                    'gateway_payment_id' => $result['gateway_payment_id'],
                    'external_reference' => $result['invoice_id'],
                ]);

                return $this->ignored($result);
            }

            $this->recordAttempt($invoice, $result);

            $this->dispatcher->dispatch(new WebhookContext($event, $invoice, $result, $result['payload'] ?? []));

            return $result;
        });
    }

    /**
     * The named event is authoritative. An event we do not know about is
     * reported and dropped instead of being coerced into a local status —
     * that coercion is what used to turn chargebacks into "pending".
     *
     * The normalized status is only consulted for gateways that do not name
     * their events at all.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $result
     */
    private function resolveEvent(array $payload, array $result): ?GatewayEvent
    {
        $named = $result['event'] ?? $payload['event'] ?? null;

        if (is_string($named) && $named !== '') {
            return GatewayEvent::tryFrom($named);
        }

        return GatewayEvent::fromNormalizedStatus((string) ($result['status'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function ignored(array $result): array
    {
        return array_merge($result, ['status' => 'ignored']);
    }

    private function chargeWithCard(VenueInvoice|CorporationInvoice $invoice, array $paymentData, User $user): array
    {
        $methodId = $paymentData['payment_method_id'] ?? null;

        if ($methodId) {
            // Scoped to the authenticated user: an unscoped lookup allowed any
            // tenant to charge their own invoice on another user's card.
            $method = $user->paymentMethods()->findOrFail($methodId);

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

        $corporation = $invoice->corporation;

        $document = $user->paymentMethods()->whereNotNull('holder_document')->value('holder_document')
            ?? $corporation?->tax_id;

        return $this->customerResolver->resolve($corporation, $user, $gatewayName, $document);
    }

    /**
     * Billing belongs to the company, so the card is filed under the
     * corporation the user is currently operating.
     */
    private function resolveUserCorporation(User $user): ?Corporation
    {
        return $user->currentVenue?->corporation ?? $user->ownedCorporation;
    }

    private function recordAttempt(VenueInvoice|CorporationInvoice $invoice, array $result): void
    {
        $invoiceType = $invoice instanceof VenueInvoice ? 'venue' : 'corporation';

        PaymentAttempt::create([
            'invoice_type' => $invoiceType,
            'invoice_id' => $invoice->id,
            'gateway' => config('subscription.payment.default', 'fake'),
            'gateway_payment_id' => $result['gateway_payment_id'],
            'amount' => $result['payload']['amount'] ?? (int) $invoice->total_value,
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

        return match ($type) {
            'corporation' => CorporationInvoice::find($id),
            // Tipo vazio vem de cobranças anteriores à referência tipada, que
            // sempre apontavam para faturas de unidade.
            'venue', '' => VenueInvoice::find($id),
            default => null,
        };
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
            'base' => (int) $subscription->base_value,
            'modules' => (int) $subscription->modules_value,
            'metered' => (int) $subscription->metered_value,
            'dedicated_surcharge' => (int) $subscription->dedicated_surcharge,
            'total' => (int) $subscription->total_value,
        ];

        $this->syncGatewayValue($result, (int) $calculated['total']);

        $total = $result['status'] === 'pending' ? (int) $calculated['total'] : (int) $result['amount'];

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

        $total = $result['status'] === 'pending' ? $breakdown['total'] : (int) $result['amount'];

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
     * @param  array{venues?: array<int|string, array<string, mixed>>, total?: int}  $calculated
     * @return array{base: int, modules: int, metered: int, dedicated_surcharge: int, total: int}
     */
    private function aggregateCorporationBreakdown(array $calculated): array
    {
        $breakdown = ['base' => 0, 'modules' => 0, 'metered' => 0, 'dedicated_surcharge' => 0];

        foreach ($calculated['venues'] ?? [] as $venueTotals) {
            $breakdown['base'] += (int) ($venueTotals['base'] ?? 0);
            $breakdown['modules'] += (int) ($venueTotals['modules'] ?? 0);
            $breakdown['metered'] += (int) ($venueTotals['metered'] ?? 0);
            $breakdown['dedicated_surcharge'] += (int) ($venueTotals['dedicated_surcharge'] ?? 0);
        }

        $breakdown['total'] = (int) ($calculated['total'] ?? 0);

        return $breakdown;
    }

    private function syncGatewayValue(array $result, int $expectedValue): void
    {
        if ($result['status'] !== 'pending') {
            return;
        }

        if ($expectedValue === (int) $result['amount']) {
            return;
        }

        try {
            $this->gateway->updatePaymentValue($result['gateway_payment_id'], $expectedValue);
        } catch (GatewayRequestException $exception) {
            // A cobrança pode ter sido confirmada entre a emissão e este
            // webhook. Abortar aqui impediria o espelhamento da fatura.
            Log::warning('gateway.webhook.payment_value_not_updated', [
                'gateway_payment_id' => $result['gateway_payment_id'],
                'expected' => $expectedValue,
                'reason' => $exception->getMessage(),
            ]);
        }
    }
}
