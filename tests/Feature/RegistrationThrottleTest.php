<?php

namespace Tests\Feature;

use Tests\RefreshAllDatabases;
use Tests\TestCase;

class RegistrationThrottleTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_registration_attempts_are_rate_limited(): void
    {
        // Fortify does not ship a limiter for the registration routes, so an IP
        // could otherwise create accounts (and verification e-mails) in bulk.
        $invalidPayload = [
            'name' => 'Throttled',
            'email' => 'not-an-email',
            'password' => 'password-1234',
            'password_confirmation' => 'password-1234',
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('register.store'), $invalidPayload)->assertStatus(302);
        }

        $this->post(route('register.store'), $invalidPayload)->assertStatus(429);
    }
}
