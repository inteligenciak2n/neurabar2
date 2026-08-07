<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trial Configuration
    |--------------------------------------------------------------------------
    */
    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Grace Period Configuration
    |--------------------------------------------------------------------------
    */
    'grace_period_days' => (int) env('BILLING_GRACE_PERIOD_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | Default Billing Day
    |--------------------------------------------------------------------------
    */
    'default_billing_day' => (int) env('BILLING_DEFAULT_BILLING_DAY', 1),

    /*
    |--------------------------------------------------------------------------
    | Minimum Due Days
    |--------------------------------------------------------------------------
    |
    | Prazo mínimo, em dias, entre a emissão e o vencimento de uma fatura.
    | Impede que a fatura nasça vencida quando o dia de cobrança contratado
    | já passou (ou é o próprio dia da geração).
    |
    */
    'minimum_due_days' => (int) env('BILLING_MINIMUM_DUE_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */
    'currency' => env('BILLING_CURRENCY', 'BRL'),

    /*
    |--------------------------------------------------------------------------
    | Default Plan
    |--------------------------------------------------------------------------
    */
    'default_plan_code' => env('BILLING_DEFAULT_PLAN_CODE', 'pro'),
];
