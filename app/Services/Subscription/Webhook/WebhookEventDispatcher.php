<?php

namespace App\Services\Subscription\Webhook;

use App\Enums\GatewayEvent;
use App\Services\Subscription\Webhook\Handlers\ChargebackHandler;
use App\Services\Subscription\Webhook\Handlers\PaymentRefusedHandler;
use App\Services\Subscription\Webhook\Handlers\SettlePaymentHandler;
use App\Services\Subscription\Webhook\Handlers\SyncPaymentHandler;
use App\Services\Subscription\Webhook\Handlers\TransitionInvoiceStatusHandler;
use App\Services\Subscription\Webhook\Handlers\WebhookEventHandler;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;

/**
 * Routes a gateway event to the handler that owns that business rule.
 *
 * Before this, every webhook went through a single `match` on the payment
 * status whose `default` arm silently turned anything unknown — chargebacks,
 * capture refusals, deletions — into `pending`.
 */
class WebhookEventDispatcher
{
    /** @var array<string, class-string<WebhookEventHandler>> */
    private const HANDLERS = [
        GatewayEvent::PaymentCreated->value => SyncPaymentHandler::class,
        GatewayEvent::PaymentUpdated->value => SyncPaymentHandler::class,
        GatewayEvent::PaymentRestored->value => SyncPaymentHandler::class,

        GatewayEvent::PaymentConfirmed->value => SettlePaymentHandler::class,
        GatewayEvent::PaymentReceived->value => SettlePaymentHandler::class,
        GatewayEvent::PaymentApprovedByRiskAnalysis->value => SettlePaymentHandler::class,

        GatewayEvent::PaymentOverdue->value => TransitionInvoiceStatusHandler::class,
        GatewayEvent::PaymentDeleted->value => TransitionInvoiceStatusHandler::class,
        GatewayEvent::PaymentRefunded->value => TransitionInvoiceStatusHandler::class,
        GatewayEvent::PaymentPartiallyRefunded->value => TransitionInvoiceStatusHandler::class,
        GatewayEvent::PaymentReceivedInCashUndone->value => TransitionInvoiceStatusHandler::class,

        GatewayEvent::PaymentCreditCardCaptureRefused->value => PaymentRefusedHandler::class,
        GatewayEvent::PaymentReprovedByRiskAnalysis->value => PaymentRefusedHandler::class,

        GatewayEvent::PaymentChargebackRequested->value => ChargebackHandler::class,
        GatewayEvent::PaymentChargebackDispute->value => ChargebackHandler::class,
        GatewayEvent::PaymentAwaitingChargebackReversal->value => ChargebackHandler::class,
    ];

    public function __construct(private readonly Container $container) {}

    /**
     * @return bool whether a handler was found and executed
     */
    public function dispatch(WebhookContext $context): bool
    {
        $handlerClass = self::HANDLERS[$context->event->value] ?? null;

        if ($handlerClass === null) {
            Log::info('gateway.webhook.event_not_handled', $context->logContext());

            return false;
        }

        $this->container->make($handlerClass)->handle($context);

        return true;
    }

    public static function handles(GatewayEvent $event): bool
    {
        return isset(self::HANDLERS[$event->value]);
    }
}
