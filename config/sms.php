<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS / OTP Provider
    |--------------------------------------------------------------------------
    |
    | The provider implementation resolved behind the App\Facades\Sms facade.
    | Must implement App\Contracts\Sms\SmsProviderContract.
    |
    | There is deliberately NO default value: booting production without an
    | explicit provider would silently fall back to the fake implementation,
    | "sending" SMS/OTP codes without ever reaching the customer's phone.
    | AppServiceProvider aborts the boot when this is empty outside local/testing.
    |
    */
    'provider' => env('SMS_PROVIDER'),
];
