<?php

namespace Tests\Feature\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\UserRole;
use App\Exceptions\Subscription\GatewayRequestException;
use App\Models\Tenant\UserPaymentMethod;
use App\Models\Tenant\Venue;
use App\Models\User;
use Mockery\MockInterface;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class PaymentMethodSecurityTest extends TestCase
{
    use RefreshAllDatabases;

    private User $user;

    private Venue $venue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->venue = Venue::factory()->create();
        $this->user = $this->loginAs(UserRole::Owner, $this->venue);
    }

    public function test_payment_method_props_do_not_expose_gateway_token_or_holder_document(): void
    {
        UserPaymentMethod::factory()->create([
            'user_id' => $this->user->id,
            'gateway_token' => 'tok_super_secret',
            'holder_document' => '12345678900',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('settings.subscription.payment-methods.index'))
            ->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Subscription/PaymentMethods')
            ->has('paymentMethods', 1)
            ->missing('paymentMethods.0.gateway_token')
            ->missing('paymentMethods.0.holder_document')
        );

        $response->assertDontSee('tok_super_secret');
        $response->assertDontSee('12345678900');
    }

    public function test_card_data_is_never_flashed_back_to_the_session(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.subscription.payment-methods.store'), [
                'number' => '4111111111111111',
                'holder_name' => 'John Doe',
                'expiration_month' => 12,
                'expiration_year' => now()->addYear()->year,
                'cvv' => '123',
            ])
            ->assertSessionHasErrors('holder_document');

        $oldInput = session('_old_input', []);

        $this->assertArrayNotHasKey('number', $oldInput);
        $this->assertArrayNotHasKey('cvv', $oldInput);
        $this->assertArrayNotHasKey('holder_document', $oldInput);
        $this->assertArrayHasKey('holder_name', $oldInput);
    }

    public function test_gateway_failure_returns_a_generic_error_instead_of_a_server_error(): void
    {
        $this->mock(PaymentGatewayContract::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createCustomer')->andReturn('cus_1');
            $mock->shouldReceive('saveCard')
                ->andThrow(new GatewayRequestException('O cartão 4111111111111111 foi recusado pelo emissor.'));
        });

        $response = $this->actingAs($this->user)
            ->post(route('settings.subscription.payment-methods.store'), [
                'number' => '4111111111111111',
                'holder_name' => 'John Doe',
                'holder_document' => '12345678900',
                'holder_email' => 'john@example.com',
                'holder_postal_code' => '01311000',
                'holder_address_number' => '100',
                'holder_phone' => '11999999999',
                'expiration_month' => 12,
                'expiration_year' => now()->addYear()->year,
                'cvv' => '123',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('number');

        $errors = session('errors')->get('number');

        $this->assertStringNotContainsString('4111111111111111', $errors[0]);

        $this->assertDatabaseMissing('user_payment_methods', [
            'user_id' => $this->user->id,
        ]);
    }
}
