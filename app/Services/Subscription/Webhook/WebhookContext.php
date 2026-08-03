<?php

namespace App\Services\Subscription\Webhook;

use App\Enums\GatewayEvent;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\VenueInvoice;

/**
 * Everything a webhook handler needs, already resolved and normalized.
 */
class WebhookContext
{
    /**
     * @param  array<string, mixed>  $result  normalized payload produced by the gateway adapter
     * @param  array<string, mixed>  $payload  raw payload as received from the gateway
     */
    public function __construct(
        public readonly GatewayEvent $event,
        public readonly VenueInvoice|CorporationInvoice $invoice,
        public readonly array $result,
        public readonly array $payload,
    ) {}

    public function gatewayPaymentId(): string
    {
        return (string) ($this->result['gateway_payment_id'] ?? '');
    }

    public function amount(): float
    {
        return (float) ($this->result['amount'] ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function logContext(): array
    {
        return [
            'event' => $this->event->value,
            'gateway_payment_id' => $this->gatewayPaymentId(),
            'invoice_type' => $this->invoice instanceof VenueInvoice ? 'venue' : 'corporation',
            'invoice_id' => $this->invoice->getKey(),
        ];
    }
}
