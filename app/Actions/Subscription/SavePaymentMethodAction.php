<?php

namespace App\Actions\Subscription;

use App\Models\User;
use App\Services\Subscription\PaymentSaasService;

class SavePaymentMethodAction
{
    public function __construct(private readonly PaymentSaasService $paymentService) {}

    public function execute(User $user, array $data): void
    {
        $this->paymentService->saveCard($user, $data, $data['billing_address'] ?? []);
    }
}
