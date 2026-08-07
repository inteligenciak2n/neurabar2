<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateway
    |--------------------------------------------------------------------------
    |
    | The gateway implementation used by PaymentSaasService. Must implement
    | App\Contracts\Subscription\PaymentGatewayContract.
    |
    | There is deliberately NO default value: booting production without an
    | explicit gateway would silently fall back to the fake implementation,
    | "activating" subscriptions and marking invoices as paid without ever
    | charging anyone. AppServiceProvider aborts the boot when this is empty.
    |
    */
    'payment' => [
        'gateway' => env('SUBSCRIPTION_PAYMENT_GATEWAY'),
        'webhook_token' => env('SUBSCRIPTION_PAYMENT_WEBHOOK_TOKEN'),
        'default' => env('SUBSCRIPTION_PAYMENT_GATEWAY_NAME', 'fake'),

        /*
        | Gateways accepted by the webhook endpoint. Anything else is rejected
        | at the routing layer so arbitrary values never reach the database.
        */
        'supported_gateways' => ['asaas', 'fake'],

        /*
        | Header carrying the authentication token for each gateway. Asaas
        | sends it in `asaas-access-token` — not as a bearer token.
        */
        'webhook_token_headers' => [
            'asaas' => ['asaas-access-token'],
            'fake' => ['X-Webhook-Token'],
        ],
    ],
];
