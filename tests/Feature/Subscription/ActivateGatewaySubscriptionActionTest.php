<?php

namespace Tests\Feature\Subscription;

use App\Actions\Subscription\ActivateGatewaySubscriptionAction;
use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\BillingMode;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\UserPaymentMethod;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueSubscription;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ActivateGatewaySubscriptionActionTest extends TestCase
{
    use RefreshAllDatabases;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_activates_unified_corporation_subscription(): void
    {
        $corporation = Corporation::factory()->create();
        $subscription = CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Active,
            'billing_day' => 10,
        ]);
        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $subscription->id,
            'base_value' => 100.0,
            'total_value' => 100.0,
            'status' => SubscriptionStatus::Active,
        ]);

        $paymentMethod = UserPaymentMethod::factory()->create([
            'user_id' => $corporation->owner_id,
            'gateway_token' => 'fake_card_token',
        ]);

        $result = app(ActivateGatewaySubscriptionAction::class)->execute($subscription, $paymentMethod);

        $this->assertTrue($result->isBilledByGateway());
        $this->assertNotNull($result->gateway_customer_id);
        $this->assertDatabaseCount('gateway_customers', 1);
        $this->assertDatabaseHas('gateway_customers', [
            'owner_type' => Corporation::class,
            'owner_id' => $corporation->id,
            'customer_id' => $result->gateway_customer_id,
        ]);
    }

    public function test_activates_venue_subscription_in_per_venue_mode(): void
    {
        $corporation = Corporation::factory()->create();
        $subscription = CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Active,
            'billing_day' => 10,
        ]);
        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        $venueSubscription = VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $subscription->id,
            'base_value' => 100.0,
            'total_value' => 100.0,
            'status' => SubscriptionStatus::Active,
        ]);

        $paymentMethod = UserPaymentMethod::factory()->create([
            'user_id' => $corporation->owner_id,
            'gateway_token' => 'fake_card_token',
        ]);

        $result = app(ActivateGatewaySubscriptionAction::class)->execute($venueSubscription, $paymentMethod);

        $this->assertTrue($result->isBilledByGateway());
        $this->assertNotNull($result->gateway_subscription_id);
    }

    public function test_reuses_existing_gateway_customer_for_same_user(): void
    {
        $corporation = Corporation::factory()->create();
        $subscription = CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Active,
            'billing_day' => 10,
        ]);

        $venueA = Venue::factory()->create(['corporation_id' => $corporation->id]);
        $venueSubscriptionA = VenueSubscription::factory()->create([
            'venue_id' => $venueA->id,
            'corporation_subscription_id' => $subscription->id,
            'base_value' => 100.0,
            'total_value' => 100.0,
            'status' => SubscriptionStatus::Active,
        ]);

        $venueB = Venue::factory()->create(['corporation_id' => $corporation->id]);
        $venueSubscriptionB = VenueSubscription::factory()->create([
            'venue_id' => $venueB->id,
            'corporation_subscription_id' => $subscription->id,
            'base_value' => 100.0,
            'total_value' => 100.0,
            'status' => SubscriptionStatus::Active,
        ]);

        $paymentMethod = UserPaymentMethod::factory()->create([
            'user_id' => $corporation->owner_id,
            'gateway_token' => 'fake_card_token',
        ]);

        $action = app(ActivateGatewaySubscriptionAction::class);
        $resultA = $action->execute($venueSubscriptionA, $paymentMethod);
        $resultB = $action->execute($venueSubscriptionB, $paymentMethod);

        $this->assertSame($resultA->gateway_customer_id, $resultB->gateway_customer_id);
        $this->assertDatabaseCount('gateway_customers', 1);
    }

    public function test_throws_when_subscription_already_billed_by_gateway(): void
    {
        $corporation = Corporation::factory()->create();
        $subscription = CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Active,
            'gateway' => 'asaas',
            'gateway_customer_id' => 'cus_123',
            'gateway_subscription_id' => 'sub_123',
        ]);

        $paymentMethod = UserPaymentMethod::factory()->create([
            'user_id' => $corporation->owner_id,
            'gateway_token' => 'fake_card_token',
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(ActivateGatewaySubscriptionAction::class)->execute($subscription, $paymentMethod);
    }

    public function test_throws_when_payment_method_has_no_gateway_token(): void
    {
        $corporation = Corporation::factory()->create();
        $subscription = CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Active,
        ]);

        $paymentMethod = UserPaymentMethod::factory()->create([
            'user_id' => $corporation->owner_id,
            'gateway_token' => '',
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(ActivateGatewaySubscriptionAction::class)->execute($subscription, $paymentMethod);
    }

    public function test_billing_day_is_preserved_when_it_fits_the_current_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-05'));

        $corporation = Corporation::factory()->create();
        $subscription = CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Active,
            'billing_day' => 30,
        ]);
        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $subscription->id,
            'base_value' => 100.0,
            'total_value' => 100.0,
            'status' => SubscriptionStatus::Active,
        ]);

        $paymentMethod = UserPaymentMethod::factory()->create([
            'user_id' => $corporation->owner_id,
            'gateway_token' => 'fake_card_token',
        ]);

        $this->mock(PaymentGatewayContract::class, function ($mock) {
            $mock->shouldReceive('createCustomer')->once()->andReturn('cus_billing_day_1');
            $mock->shouldReceive('createSubscription')
                ->once()
                ->withArgs(fn ($subscription, array $data) => $data['next_due_date'] === '2026-01-30')
                ->andReturn([
                    'gateway_subscription_id' => 'sub_billing_day_1',
                    'status' => 'active',
                    'next_due_date' => '2026-01-30',
                    'payload' => [],
                ]);
        });

        app(ActivateGatewaySubscriptionAction::class)->execute($subscription, $paymentMethod);
    }

    public function test_billing_day_is_clamped_to_the_last_day_of_a_shorter_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-02-05'));

        $corporation = Corporation::factory()->create();
        $subscription = CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified,
            'status' => SubscriptionStatus::Active,
            'billing_day' => 30,
        ]);
        $venue = Venue::factory()->create(['corporation_id' => $corporation->id]);
        VenueSubscription::factory()->create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $subscription->id,
            'base_value' => 100.0,
            'total_value' => 100.0,
            'status' => SubscriptionStatus::Active,
        ]);

        $paymentMethod = UserPaymentMethod::factory()->create([
            'user_id' => $corporation->owner_id,
            'gateway_token' => 'fake_card_token',
        ]);

        $this->mock(PaymentGatewayContract::class, function ($mock) {
            $mock->shouldReceive('createCustomer')->once()->andReturn('cus_billing_day_2');
            $mock->shouldReceive('createSubscription')
                ->once()
                ->withArgs(fn ($subscription, array $data) => $data['next_due_date'] === '2026-02-28')
                ->andReturn([
                    'gateway_subscription_id' => 'sub_billing_day_2',
                    'status' => 'active',
                    'next_due_date' => '2026-02-28',
                    'payload' => [],
                ]);
        });

        app(ActivateGatewaySubscriptionAction::class)->execute($subscription, $paymentMethod);
    }
}
