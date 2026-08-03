<?php

namespace App\Actions\Subscription;

use App\Enums\GatewayWebhookEventStatus;
use App\Exceptions\Subscription\InvalidWebhookTokenException;
use App\Jobs\Subscription\ProcessGatewayWebhookJob;
use App\Models\Tenant\GatewayWebhookEvent;
use InvalidArgumentException;

class ProcessWebhookPaymentAction
{
    /**
     * Validate the webhook token, persist the event idempotently, and
     * dispatch async processing. Returns immediately so the gateway
     * receives a fast HTTP 200 response.
     */
    public function execute(string $gateway, ?string $token, array $payload): array
    {
        $expectedToken = (string) config('subscription.payment.webhook_token');

        if ($expectedToken === '' || ! hash_equals($expectedToken, (string) $token)) {
            throw new InvalidWebhookTokenException('Invalid webhook token.');
        }

        $eventId = (string) ($payload['id'] ?? $payload['gateway_payment_id'] ?? '');
        $eventType = (string) ($payload['event'] ?? 'payment.updated');

        if ($eventId === '') {
            throw new InvalidArgumentException('Missing event id in payload.');
        }

        $event = GatewayWebhookEvent::firstOrCreate(
            ['gateway' => $gateway, 'event_id' => $eventId],
            [
                'event_type' => $eventType,
                'payload' => $payload,
                'status' => GatewayWebhookEventStatus::Pending,
                'received_at' => now(),
            ],
        );

        if ($event->wasRecentlyCreated || in_array($event->status, [GatewayWebhookEventStatus::Pending, GatewayWebhookEventStatus::Failed], true)) {
            ProcessGatewayWebhookJob::dispatch($event->id)->onQueue('payments');
        }

        return [
            'gateway_payment_id' => $payload['gateway_payment_id'] ?? null,
            'status' => 'queued',
            'event_id' => $eventId,
        ];
    }
}
