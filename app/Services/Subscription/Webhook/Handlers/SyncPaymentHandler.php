<?php

namespace App\Services\Subscription\Webhook\Handlers;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Exceptions\Subscription\GatewayRequestException;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;
use App\Services\Billing\SubscriptionCalculator;
use App\Services\Subscription\Webhook\WebhookContext;
use Illuminate\Support\Facades\Log;

/**
 * Keeps the local invoice and the gateway charge aligned on the current cycle
 * value (modules + metered usage).
 *
 * The old early-return meant that a `PAYMENT_CONFIRMED` arriving before
 * `PAYMENT_CREATED` froze the invoice on the plain subscription base value:
 * the invoice was mirrored from the confirmed amount and nothing ever
 * recalculated it. Now the value is always recomputed, and when the charge can
 * no longer be changed the divergence is reported instead of being swallowed.
 */
class SyncPaymentHandler implements WebhookEventHandler
{
    public function __construct(
        private readonly SubscriptionCalculator $calculator,
        private readonly PaymentGatewayContract $gateway,
    ) {}

    public function handle(WebhookContext $context): void
    {
        $invoice = $context->invoice;

        if ($invoice->wasRecentlyCreated) {
            // O espelhamento acabou de criar a fatura com o valor recalculado
            // e já alinhou a cobrança no gateway.
            return;
        }

        $expected = $this->expectedTotal($invoice);

        if ($expected === null) {
            return;
        }

        if ($invoice->status->isFinalized()) {
            $this->reportDivergence($context, $expected);

            return;
        }

        if ($expected !== (int) $invoice->total_value) {
            $invoice->update(['total_value' => $expected]);
        }

        if ($expected === $context->amount()) {
            return;
        }

        try {
            $this->gateway->updatePaymentValue($context->gatewayPaymentId(), $expected);
        } catch (GatewayRequestException $exception) {
            // A cobrança já foi confirmada ou removida no gateway. Abortar aqui
            // queimaria as 5 tentativas do job sem nunca ter sucesso.
            Log::warning('gateway.webhook.payment_value_not_updated', $context->logContext() + [
                'expected' => $expected,
                'reason' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return int|null Centavos, ou null quando o total não pode ser recalculado.
     */
    private function expectedTotal(VenueInvoice|CorporationInvoice $invoice): ?int
    {
        if ($invoice instanceof CorporationInvoice) {
            $corporation = $invoice->corporation;

            if (! $corporation) {
                return null;
            }

            return (int) ($this->calculator->calculateCorporation($corporation, $invoice->period)['total'] ?? 0);
        }

        $venue = $invoice->venue;

        if (! $venue) {
            return null;
        }

        $calculated = $this->calculator->calculateVenue($venue, $invoice->period);

        return $calculated === null ? null : (int) $calculated['total'];
    }

    private function reportDivergence(WebhookContext $context, int $expected): void
    {
        if ($expected === $context->amount()) {
            return;
        }

        Log::warning('billing.invoice.value_divergence', $context->logContext() + [
            'expected' => $expected,
            'charged' => $context->amount(),
        ]);
    }
}
