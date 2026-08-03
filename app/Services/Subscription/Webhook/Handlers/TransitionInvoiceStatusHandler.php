<?php

namespace App\Services\Subscription\Webhook\Handlers;

use App\Services\Subscription\Webhook\InvoiceStatusTransitioner;
use App\Services\Subscription\Webhook\WebhookContext;
use Illuminate\Support\Facades\Log;

/**
 * Applies the invoice status the event maps to, with no extra side effect.
 * Used by refund, overdue and deletion events.
 */
class TransitionInvoiceStatusHandler implements WebhookEventHandler
{
    public function __construct(private readonly InvoiceStatusTransitioner $transitioner) {}

    public function handle(WebhookContext $context): void
    {
        $status = $context->event->targetInvoiceStatus();

        if ($status === null) {
            Log::warning('gateway.webhook.event_without_target_status', $context->logContext());

            return;
        }

        $this->transitioner->transition(
            $context->invoice,
            $status,
            ['gateway_payment_id' => $context->gatewayPaymentId()],
            $context->logContext(),
        );
    }
}
