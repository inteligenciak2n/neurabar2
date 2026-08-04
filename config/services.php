<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'translate_region' => env('AWS_TRANSLATE_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
    ],

    'asaas' => [
        'environment' => env('ASAAS_ACCESS_ENVIRONMENT', 'sandbox'),
        'base_url' => env('ASAAS_ACCESS_ENVIRONMENT') === 'production'
            ? 'https://api.asaas.com'
            : 'https://api-sandbox.asaas.com',
        'access_token' => env('ASAAS_ACCESS_TOKEN'),

        /*
         * Quantas falhas de infraestrutura seguidas abrem o circuito e por
         * quantos segundos ele permanece aberto. Sem isso, uma indisponibilidade
         * do gateway trava cada request do usuário no timeout da chamada.
         */
        'circuit_breaker' => [
            'threshold' => (int) env('ASAAS_CIRCUIT_BREAKER_THRESHOLD', 5),
            'cooldown' => (int) env('ASAAS_CIRCUIT_BREAKER_COOLDOWN', 60),
        ],
    ],

];
