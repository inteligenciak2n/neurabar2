<?php

namespace App\Services\Subscription\Webhook\Handlers;

use App\Actions\Subscription\SuspendSubscriptionAction;
use App\Enums\ProfileEnum;
use App\Models\Tenant\VenueInvoice;
use App\Models\User;
use App\Notifications\Subscription\PaymentChargebackReceived;
use App\Services\Subscription\Webhook\InvoiceStatusTransitioner;
use App\Services\Subscription\Webhook\WebhookContext;
use Illuminate\Support\Facades\Notification;

/**
 * A chargeback means the money already left the account, so access is revoked
 * immediately and the backoffice is alerted to contest it in time.
 */
class ChargebackHandler implements WebhookEventHandler
{
    public function __construct(
        private readonly InvoiceStatusTransitioner $transitioner,
        private readonly SuspendSubscriptionAction $suspender,
    ) {}

    public function handle(WebhookContext $context): void
    {
        $status = $context->event->targetInvoiceStatus();

        if ($status !== null) {
            $this->transitioner->transition(
                $context->invoice,
                $status,
                ['gateway_payment_id' => $context->gatewayPaymentId()],
                $context->logContext(),
            );
        }

        $this->suspender->execute($context->invoice, $context->event->value);

        $this->alertBackoffice($context);
    }

    private function alertBackoffice(WebhookContext $context): void
    {
        $admins = User::query()
            ->whereIn('profile', [ProfileEnum::SuperAdmin, ProfileEnum::Finance])
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new PaymentChargebackReceived(
            $context->event->value,
            $context->invoice instanceof VenueInvoice ? 'venue' : 'corporation',
            (string) $context->invoice->getKey(),
            $context->gatewayPaymentId(),
            $context->amount(),
        ));
    }
}
