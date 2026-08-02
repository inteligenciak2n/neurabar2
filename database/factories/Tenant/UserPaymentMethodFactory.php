<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\UserPaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPaymentMethodFactory extends Factory
{
    protected $model = UserPaymentMethod::class;

    public function definition(): array
    {
        $expiresAt = now()->addMonths(fake()->numberBetween(6, 60));

        return [
            'user_id' => User::factory(),
            'gateway' => 'fake',
            'gateway_token' => 'fake_card_'.fake()->uuid(),
            'brand' => fake()->randomElement(['visa', 'mastercard', 'amex']),
            'last4' => fake()->numerify('####'),
            'holder_name' => fake()->name(),
            'holder_document' => fake()->numerify('###########'),
            'expiration_month' => $expiresAt->month,
            'expiration_year' => $expiresAt->year,
            'is_default' => false,
            'billing_address_json' => null,
        ];
    }
}
