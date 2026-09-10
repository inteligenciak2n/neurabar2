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
    | This value only takes effect in local/testing (to swap the fake driver
    | for something else). Outside those environments AppServiceProvider always
    | resolves TwilioSmsProvider and ignores this value entirely, aborting the
    | boot instead if TWILIO_ACCOUNT_SID/TWILIO_AUTH_TOKEN are missing.
    |
    */
    'provider' => env('SMS_PROVIDER'),
];
