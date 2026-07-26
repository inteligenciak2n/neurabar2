<?php

use App\Services\Subscription\FakePaymentGateway;

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateway
    |--------------------------------------------------------------------------
    |
    | The gateway implementation used by PaymentSaasService. Must implement
    | App\Contracts\Subscription\PaymentGatewayContract.
    |
    */
    'payment' => [
        'gateway' => env('SUBSCRIPTION_PAYMENT_GATEWAY', FakePaymentGateway::class),
        'webhook_token' => env('SUBSCRIPTION_PAYMENT_WEBHOOK_TOKEN'),
        'default' => env('SUBSCRIPTION_PAYMENT_GATEWAY_NAME', 'fake'),
    ],
];
