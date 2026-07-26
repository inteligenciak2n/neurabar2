<?php

namespace App\Actions\Subscription;

use App\Services\Subscription\PaymentSaasService;
use InvalidArgumentException;

class ProcessWebhookPaymentAction
{
    public function __construct(private readonly PaymentSaasService $paymentService) {}

    public function execute(string $gateway, string $token, array $payload): array
    {
        $expectedToken = config('subscription.payment.webhook_token');

        if ($expectedToken && $token !== $expectedToken) {
            throw new InvalidArgumentException('Invalid webhook token.');
        }

        return $this->paymentService->handleWebhook($gateway, $payload);
    }
}
