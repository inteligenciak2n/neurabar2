<?php

namespace App\Services\Subscription\Webhook\Handlers;

use App\Models\User;
use App\Notifications\Billing\CardPaymentRefused;
use App\Services\Subscription\Webhook\WebhookContext;
use Illuminate\Support\Facades\Log;

/**
 * The card was declined but the debt still stands: the invoice keeps its
 * status so the dunning job (`RetryOverdueInvoicesJob`) picks it up, and the
 * customer is told right away instead of only finding out at suspension time.
 */
class PaymentRefusedHandler implements WebhookEventHandler
{
    public function handle(WebhookContext $context): void
    {
        Log::warning('gateway.webhook.payment_refused', $context->logContext() + [
            'amount' => $context->amount(),
        ]);

        $owner = $context->invoice->corporation?->owner;

        if (! $owner instanceof User) {
            return;
        }

        $owner->notify(new CardPaymentRefused($context->invoice));
    }
}
