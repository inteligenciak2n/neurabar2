<?php

namespace Tests\Feature\Subscription;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\UserRole;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\Venue;
use App\Models\User;
use App\Services\Subscription\AsaasPaymentGateway;
use Illuminate\Support\Facades\Http;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

/**
 * Exercises the real gateway implementation end to end (controller → service →
 * HTTP), which the mocked tests cannot cover: request shape, headers and the
 * mapping of the provider's response were all unverified.
 */
class AsaasGatewayIntegrationTest extends TestCase
{
    use RefreshAllDatabases;

    private User $user;

    private Venue $venue;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.asaas.base_url' => 'https://sandbox.asaas.test',
            'services.asaas.access_token' => 'test_token',
            'subscription.payment.default' => 'asaas',
        ]);

        $this->app->bind(PaymentGatewayContract::class, AsaasPaymentGateway::class);

        $this->venue = Venue::factory()->create();
        $this->user = $this->loginAs(UserRole::Owner, $this->venue);
    }

    /**
     * @return array<string, mixed>
     */
    private function cardPayload(): array
    {
        return [
            'number' => '4111111111111111',
            'holder_name' => 'John Doe',
            'holder_document' => '12345678909',
            'holder_email' => 'john@example.com',
            'holder_postal_code' => '01310100',
            'holder_address_number' => '1000',
            'holder_phone' => '11999998888',
            'expiration_month' => 12,
            'expiration_year' => now()->addYear()->year,
            'cvv' => '123',
        ];
    }

    public function test_saving_a_card_creates_the_customer_and_tokenizes_through_the_gateway(): void
    {
        Http::fake([
            '*/v3/customers' => Http::response(['id' => 'cus_asaas_1'], 200),
            '*/v3/creditCard/tokenizeCreditCard' => Http::response([
                'creditCardToken' => 'tok_asaas_1',
                'creditCardBrand' => 'VISA',
                'creditCardNumber' => '1111',
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->post(route('settings.subscription.payment-methods.store'), $this->cardPayload())
            ->assertRedirect();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v3/customers')
            && $request->hasHeader('access_token', 'test_token')
            && $request['cpfCnpj'] === '12345678909');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v3/creditCard/tokenizeCreditCard')
            && $request['customer'] === 'cus_asaas_1'
            && $request['creditCard']['number'] === '4111111111111111');

        $this->assertDatabaseHas('user_payment_methods', [
            'user_id' => $this->user->id,
            'gateway_token' => 'tok_asaas_1',
            'brand' => 'visa',
            'last4' => '1111',
        ]);

        $this->assertDatabaseHas('gateway_customers', [
            'owner_type' => Corporation::class,
            'owner_id' => $this->venue->corporation_id,
            'customer_id' => 'cus_asaas_1',
        ]);
    }

    public function test_gateway_error_is_surfaced_as_a_validation_message(): void
    {
        Http::fake([
            '*/v3/customers' => Http::response([
                'errors' => [['code' => 'invalid_cpfCnpj', 'description' => 'CPF/CNPJ inválido.']],
            ], 400),
        ]);

        $this->actingAs($this->user)
            ->post(route('settings.subscription.payment-methods.store'), $this->cardPayload())
            ->assertRedirect()
            ->assertSessionHasErrors('number');

        $this->assertDatabaseCount('user_payment_methods', 0);
        $this->assertDatabaseCount('gateway_customers', 0);
    }

    public function test_two_users_of_the_same_corporation_share_one_gateway_customer(): void
    {
        Http::fake([
            '*/v3/customers' => Http::response(['id' => 'cus_shared_1'], 200),
            '*/v3/creditCard/tokenizeCreditCard' => Http::response([
                'creditCardToken' => 'tok_shared',
                'creditCardBrand' => 'VISA',
                'creditCardNumber' => '4444',
            ], 200),
        ]);

        $manager = $this->loginAs(UserRole::GeneralManager, $this->venue);

        foreach ([$this->user, $manager] as $actor) {
            $this->actingAs($actor)
                ->post(route('settings.subscription.payment-methods.store'), $this->cardPayload())
                ->assertRedirect();
        }

        $this->assertDatabaseCount('gateway_customers', 1);
        Http::assertSentCount(3);
    }
}
