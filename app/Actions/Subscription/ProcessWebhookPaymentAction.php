<?php

namespace App\Actions\Subscription;

use App\Exceptions\Subscription\InvalidWebhookTokenException;
use App\Services\Subscription\PaymentSaasService;

class ProcessWebhookPaymentAction
{
    public function __construct(private readonly PaymentSaasService $paymentService) {}

    public function execute(string $gateway, ?string $token, array $payload): array
    {
        $expectedToken = config('subscription.payment.webhook_token');

        if (! $expectedToken || $token !== $expectedToken) {
            throw new InvalidWebhookTokenException('Invalid webhook token.');
        }

        return $this->paymentService->handleWebhook($gateway, $payload);
    }
}
