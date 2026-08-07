<?php

namespace App\Services\Subscription\Webhook\Handlers;

use App\Actions\Subscription\ReactivateSubscriptionAction;
use App\Enums\InvoiceStatus;
use App\Services\Subscription\Webhook\InvoiceStatusTransitioner;
use App\Services\Subscription\Webhook\WebhookContext;

class SettlePaymentHandler implements WebhookEventHandler
{
    public function __construct(
        private readonly InvoiceStatusTransitioner $transitioner,
        private readonly ReactivateSubscriptionAction $reactivator,
    ) {}

    public function handle(WebhookContext $context): void
    {
        $applied = $this->transitioner->transition(
            $context->invoice,
            InvoiceStatus::Paid,
            [
                'gateway_payment_id' => $context->gatewayPaymentId(),
                'paid_at' => now(),
            ],
            $context->logContext(),
        );

        if (! $applied) {
            return;
        }

        $this->reactivator->execute($context->invoice);
    }
}
