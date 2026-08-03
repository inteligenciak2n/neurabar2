<?php

namespace Tests\Feature\Subscription;

use App\Enums\BillingMode;
use App\Enums\InvoiceStatus;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\PaymentSaasMethod;
use App\Enums\ProfileEnum;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Jobs\Subscription\ProcessGatewayWebhookJob;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\UserPaymentMethod;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueModule;
use App\Models\User;
use App\Notifications\Subscription\GatewayAccessTokenExpiringSoon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class PortalSubscriptionTest extends TestCase
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

    public function test_owner_can_view_subscription_index(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.subscription.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Subscription/Index')
                ->has('subscription')
                ->has('corporation')
                ->has('availableModules')
                ->has('venues')
            );
    }

    public function test_non_manager_cannot_access_subscription_pages(): void
    {
        $attendantVenue = Venue::factory()->create();
        $attendant = $this->loginAs(UserRole::Attendant, $attendantVenue);

        $this->actingAs($attendant)
            ->get(route('settings.subscription.index'))
            ->assertForbidden();
    }

    public function test_owner_can_activate_module_for_venue(): void
    {
        $catalog = ModuleCatalog::firstWhere('code', ModuleCode::Kds->value)
            ?? ModuleCatalog::factory()->create([
                'code' => ModuleCode::Kds->value,
                'active' => true,
                'base_monthly_price' => 50,
            ]);

        CorporationModule::factory()->create([
            'corporation_id' => $this->venue->corporation_id,
            'module_code' => $catalog->code,
            'status' => ModuleStatus::Active,
        ]);

        $this->actingAs($this->user)
            ->post(route('settings.subscription.modules.store', $this->venue), [
                'module_code' => $catalog->code,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('venue_modules', [
            'venue_id' => $this->venue->id,
            'module_code' => $catalog->code,
            'status' => ModuleStatus::Active->value,
        ]);
    }

    public function test_owner_can_deactivate_module_for_venue(): void
    {
        $catalog = ModuleCatalog::firstWhere('code', ModuleCode::Kds->value)
            ?? ModuleCatalog::factory()->create([
                'code' => ModuleCode::Kds->value,
                'active' => true,
            ]);

        CorporationModule::factory()->create([
            'corporation_id' => $this->venue->corporation_id,
            'module_code' => $catalog->code,
            'status' => ModuleStatus::Active,
        ]);

        VenueModule::factory()->create([
            'venue_id' => $this->venue->id,
            'module_code' => $catalog->code,
            'status' => ModuleStatus::Active,
        ]);

        $this->actingAs($this->user)
            ->delete(route('settings.subscription.modules.destroy', [$this->venue, $catalog->code]))
            ->assertRedirect();

        $this->assertDatabaseHas('venue_modules', [
            'venue_id' => $this->venue->id,
            'module_code' => $catalog->code,
            'status' => ModuleStatus::Inactive->value,
        ]);
    }

    public function test_owner_can_save_credit_card(): void
    {
        $this->actingAs($this->user)
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
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_payment_methods', [
            'user_id' => $this->user->id,
            'holder_name' => 'John Doe',
            'brand' => 'visa',
            'last4' => '1111',
            'is_default' => true,
        ]);
    }

    public function test_saving_credit_card_requires_asaas_holder_fields(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.subscription.payment-methods.store'), [
                'number' => '4111111111111111',
                'holder_name' => 'John Doe',
                'expiration_month' => 12,
                'expiration_year' => now()->addYear()->year,
                'cvv' => '123',
            ])
            ->assertSessionHasErrors([
                'holder_document',
                'holder_email',
                'holder_postal_code',
                'holder_address_number',
                'holder_phone',
            ]);
    }

    public function test_owner_cannot_pay_invoice_with_another_users_card(): void
    {
        $invoice = VenueInvoice::factory()->create([
            'venue_id' => $this->venue->id,
            'status' => InvoiceStatus::Open,
            'is_finalized' => false,
        ]);

        $otherUser = User::factory()->create();
        $foreignMethod = UserPaymentMethod::factory()->create([
            'user_id' => $otherUser->id,
            'expiration_year' => now()->addYear()->year,
        ]);

        $this->actingAs($this->user)
            ->post(route('settings.subscription.invoices.pay', ['venue', $invoice->id]), [
                'method' => PaymentSaasMethod::CreditCard->value,
                'payment_method_id' => $foreignMethod->id,
            ])
            ->assertSessionHasErrors('payment_method_id');

        $this->assertDatabaseHas('venue_invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::Open->value,
        ]);
    }

    public function test_owner_can_pay_invoice_with_credit_card(): void
    {
        $method = UserPaymentMethod::factory()->create([
            'user_id' => $this->user->id,
            'gateway_token' => 'fake_card_token',
            'is_default' => true,
        ]);

        $invoice = VenueInvoice::factory()->create([
            'venue_id' => $this->venue->id,
            'status' => InvoiceStatus::Open,
            'total_value' => 150,
            'due_date' => now()->addDays(5),
            'period' => now()->format('Y-m'),
        ]);

        $this->actingAs($this->user)
            ->post(route('settings.subscription.invoices.pay', ['venue', $invoice->id]), [
                'method' => PaymentSaasMethod::CreditCard->value,
                'payment_method_id' => $method->id,
            ])
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid->value, $invoice->status->value);
        $this->assertNotNull($invoice->paid_at);
        $this->assertTrue($invoice->is_finalized);
    }

    public function test_paying_the_last_overdue_invoice_reactivates_a_suspended_subscription(): void
    {
        $subscription = $this->venue->subscription;
        $subscription->update(['status' => SubscriptionStatus::Suspended]);

        $method = UserPaymentMethod::factory()->create([
            'user_id' => $this->user->id,
            'gateway_token' => 'fake_card_token',
            'is_default' => true,
        ]);

        $invoice = VenueInvoice::factory()->create([
            'venue_id' => $this->venue->id,
            'status' => InvoiceStatus::Overdue,
            'total_value' => 150,
            'due_date' => now()->subDays(10),
            'period' => now()->format('Y-m'),
        ]);

        $this->actingAs($this->user)
            ->post(route('settings.subscription.invoices.pay', ['venue', $invoice->id]), [
                'method' => PaymentSaasMethod::CreditCard->value,
                'payment_method_id' => $method->id,
            ])
            ->assertRedirect();

        $this->assertSame(SubscriptionStatus::Active->value, $subscription->refresh()->status->value);
    }

    public function test_subscription_stays_suspended_while_another_invoice_is_overdue(): void
    {
        $subscription = $this->venue->subscription;
        $subscription->update(['status' => SubscriptionStatus::Suspended]);

        $method = UserPaymentMethod::factory()->create([
            'user_id' => $this->user->id,
            'gateway_token' => 'fake_card_token',
            'is_default' => true,
        ]);

        VenueInvoice::factory()->create([
            'venue_id' => $this->venue->id,
            'status' => InvoiceStatus::Overdue,
            'total_value' => 150,
            'due_date' => now()->subDays(40),
            'period' => now()->subMonth()->format('Y-m'),
        ]);

        $invoice = VenueInvoice::factory()->create([
            'venue_id' => $this->venue->id,
            'status' => InvoiceStatus::Overdue,
            'total_value' => 150,
            'due_date' => now()->subDays(10),
            'period' => now()->format('Y-m'),
        ]);

        $this->actingAs($this->user)
            ->post(route('settings.subscription.invoices.pay', ['venue', $invoice->id]), [
                'method' => PaymentSaasMethod::CreditCard->value,
                'payment_method_id' => $method->id,
            ])
            ->assertRedirect();

        $this->assertSame(SubscriptionStatus::Suspended->value, $subscription->refresh()->status->value);
    }

    public function test_owner_can_generate_pix_for_invoice(): void
    {
        $invoice = VenueInvoice::factory()->create([
            'venue_id' => $this->venue->id,
            'status' => InvoiceStatus::Open,
            'total_value' => 150,
            'due_date' => now()->addDays(5),
            'period' => now()->format('Y-m'),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('settings.subscription.invoices.pay', ['venue', $invoice->id]), [
                'method' => PaymentSaasMethod::Pix->value,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('payment_attempts', [
            'invoice_type' => 'venue',
            'invoice_id' => $invoice->id,
            'status' => 'pending',
        ]);
    }

    public function test_webhook_updates_invoice_status_to_paid(): void
    {
        $invoice = VenueInvoice::factory()->create([
            'venue_id' => $this->venue->id,
            'status' => InvoiceStatus::Open,
            'total_value' => 150,
            'due_date' => now()->addDays(5),
            'period' => now()->format('Y-m'),
        ]);

        config(['subscription.payment.webhook_token' => 'test-token']);

        $this->postJson(route('api.webhooks.payment', 'fake'), [
            'gateway_payment_id' => 'fake_pix_123',
            'status' => 'paid',
            'invoice_type' => 'venue',
            'invoice_id' => $invoice->id,
            'amount' => 150,
        ], [
            'Authorization' => 'Bearer test-token',
        ])->assertOk();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid->value, $invoice->status->value);
    }

    public function test_webhook_rejects_invalid_token(): void
    {
        config(['subscription.payment.webhook_token' => 'test-token']);

        $this->postJson(route('api.webhooks.payment', 'fake'), [
            'gateway_payment_id' => 'fake_pix_123',
            'status' => 'paid',
        ], [
            'Authorization' => 'Bearer wrong-token',
        ])->assertUnauthorized();
    }

    public function test_owner_can_update_billing_address(): void
    {
        $this->actingAs($this->user)
            ->put(route('settings.subscription.billing-address.update', 'corporation'), [
                'street' => 'Rua Teste',
                'number' => '123',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'zip_code' => '01001000',
                'billing_tax_regime' => 'Simples Nacional',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('corporations', [
            'id' => $this->venue->corporation_id,
            'billing_tax_regime' => 'Simples Nacional',
        ]);
    }

    public function test_owner_cannot_pay_invoice_from_another_corporation(): void
    {
        $otherVenue = Venue::factory()->create();
        $otherInvoice = VenueInvoice::factory()->create([
            'venue_id' => $otherVenue->id,
            'status' => InvoiceStatus::Open,
            'total_value' => 150,
        ]);

        $this->actingAs($this->user)
            ->post(route('settings.subscription.invoices.pay', ['venue', $otherInvoice->id]), [
                'method' => PaymentSaasMethod::Pix->value,
            ])
            ->assertForbidden();
    }

    public function test_owner_cannot_activate_module_for_venue_of_another_corporation(): void
    {
        $otherVenue = Venue::factory()->create();

        $this->actingAs($this->user)
            ->post(route('settings.subscription.modules.store', $otherVenue), [
                'module_code' => ModuleCode::Kds->value,
                'quantity' => 1,
            ])
            ->assertForbidden();
    }

    public function test_webhook_rejects_request_when_token_is_not_configured(): void
    {
        config(['subscription.payment.webhook_token' => null]);

        $this->postJson(route('api.webhooks.payment', 'fake'), [
            'gateway_payment_id' => 'fake_pix_123',
            'status' => 'paid',
        ])->assertUnauthorized();
    }

    public function test_webhook_accepts_asaas_access_token_header(): void
    {
        // Only the authentication layer is under test here; the fake gateway
        // bound in the test environment cannot process an Asaas payload.
        Queue::fake();

        config(['subscription.payment.webhook_token' => 'test-token']);

        $this->postJson(route('api.webhooks.payment', 'asaas'), [
            'id' => 'evt_asaas_header_1',
            'event' => 'PAYMENT_RECEIVED',
            'payment' => ['id' => 'pay_1', 'status' => 'RECEIVED', 'value' => 10],
        ], [
            'asaas-access-token' => 'test-token',
        ])->assertOk();

        Queue::assertPushed(ProcessGatewayWebhookJob::class);

        $this->assertDatabaseHas('gateway_webhook_events', [
            'gateway' => 'asaas',
            'event_id' => 'evt_asaas_header_1',
        ]);
    }

    public function test_webhook_rejects_asaas_request_with_wrong_access_token(): void
    {
        config(['subscription.payment.webhook_token' => 'test-token']);

        $this->postJson(route('api.webhooks.payment', 'asaas'), [
            'id' => 'evt_asaas_header_2',
            'event' => 'PAYMENT_RECEIVED',
        ], [
            'asaas-access-token' => 'wrong-token',
        ])->assertUnauthorized();

        $this->assertDatabaseMissing('gateway_webhook_events', ['event_id' => 'evt_asaas_header_2']);
    }

    public function test_webhook_rejects_unsupported_gateway(): void
    {
        config(['subscription.payment.webhook_token' => 'test-token']);

        $this->postJson('/api/webhooks/payment/stripe', ['id' => 'evt_1'], [
            'asaas-access-token' => 'test-token',
        ])->assertNotFound();
    }

    public function test_webhook_does_not_reprocess_duplicate_event(): void
    {
        $invoice = VenueInvoice::factory()->create([
            'venue_id' => $this->venue->id,
            'status' => InvoiceStatus::Open,
            'total_value' => 150,
            'due_date' => now()->addDays(5),
            'period' => now()->format('Y-m'),
        ]);

        config(['subscription.payment.webhook_token' => 'test-token']);

        $payload = [
            'id' => 'evt_duplicate_1',
            'gateway_payment_id' => 'fake_pix_123',
            'status' => 'paid',
            'invoice_type' => 'venue',
            'invoice_id' => $invoice->id,
            'amount' => 150,
        ];

        $this->postJson(route('api.webhooks.payment', 'fake'), $payload, [
            'Authorization' => 'Bearer test-token',
        ])->assertOk();

        $this->postJson(route('api.webhooks.payment', 'fake'), $payload, [
            'Authorization' => 'Bearer test-token',
        ])->assertOk();

        $this->assertDatabaseCount('gateway_webhook_events', 1);
        $this->assertDatabaseHas('gateway_webhook_events', [
            'gateway' => 'fake',
            'event_id' => 'evt_duplicate_1',
            'status' => 'processed',
        ]);
    }

    public function test_webhook_notifies_admins_when_access_token_is_expiring_soon(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['profile' => ProfileEnum::SuperAdmin]);

        config(['subscription.payment.webhook_token' => 'test-token']);

        $this->postJson(route('api.webhooks.payment', 'asaas'), [
            'id' => 'evt_access_token_1',
            'event' => 'ACCESS_TOKEN_EXPIRING_SOON',
            'dateCreated' => now()->toDateTimeString(),
            'accessToken' => [
                'id' => 'token-id',
                'name' => 'Production key',
                'projectedExpirationDateByLackOfUse' => now()->addDays(5)->toDateString(),
            ],
        ], [
            'Authorization' => 'Bearer test-token',
        ])->assertOk();

        Notification::assertSentTo($admin, GatewayAccessTokenExpiringSoon::class);

        $this->assertDatabaseHas('gateway_webhook_events', [
            'gateway' => 'asaas',
            'event_id' => 'evt_access_token_1',
            'event_type' => 'ACCESS_TOKEN_EXPIRING_SOON',
            'status' => 'processed',
        ]);
    }

    public function test_reactivating_module_preserves_original_started_at(): void
    {
        $catalog = ModuleCatalog::firstWhere('code', ModuleCode::Kds->value)
            ?? ModuleCatalog::factory()->create([
                'code' => ModuleCode::Kds->value,
                'active' => true,
            ]);

        CorporationModule::factory()->create([
            'corporation_id' => $this->venue->corporation_id,
            'module_code' => $catalog->code,
            'status' => ModuleStatus::Active,
        ]);

        $originalStartedAt = now()->subDays(10);

        VenueModule::factory()->create([
            'venue_id' => $this->venue->id,
            'module_code' => $catalog->code,
            'status' => ModuleStatus::Inactive,
            'started_at' => $originalStartedAt,
        ]);

        $this->actingAs($this->user)
            ->post(route('settings.subscription.modules.store', $this->venue), [
                'module_code' => $catalog->code,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('venue_modules', [
            'venue_id' => $this->venue->id,
            'module_code' => $catalog->code,
            'status' => ModuleStatus::Active->value,
            'started_at' => $originalStartedAt->toDateTimeString(),
        ]);
    }

    public function test_credit_card_payment_failure_is_recorded(): void
    {
        $method = UserPaymentMethod::factory()->create([
            'user_id' => $this->user->id,
            'gateway_token' => 'fake_card_token',
            'is_default' => true,
        ]);

        $invoice = VenueInvoice::factory()->create([
            'venue_id' => $this->venue->id,
            'status' => InvoiceStatus::Open,
            'total_value' => 150,
            'due_date' => now()->addDays(5),
            'period' => now()->format('Y-m'),
        ]);

        $this->actingAs($this->user)
            ->post(route('settings.subscription.invoices.pay', ['venue', $invoice->id]), [
                'method' => PaymentSaasMethod::CreditCard->value,
                'payment_method_id' => $method->id,
                'simulate_failure' => true,
            ])
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Open->value, $invoice->status->value);
        $this->assertDatabaseHas('payment_attempts', [
            'invoice_type' => 'venue',
            'invoice_id' => $invoice->id,
            'status' => 'failed',
        ]);
    }

    public function test_owner_can_cancel_subscription(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.subscription.cancel'))
            ->assertRedirect();

        $subscription = $this->venue->corporation->subscriptions()->latest('started_at')->first();

        $this->assertNotNull($subscription);
        $this->assertNotNull($subscription->ended_at);
        $this->assertSame(SubscriptionStatus::Canceled->value, $subscription->status->value);
    }

    public function test_owner_can_activate_gateway_subscription_for_venue(): void
    {
        UserPaymentMethod::factory()->create([
            'user_id' => $this->user->id,
            'gateway_token' => 'fake_card_token',
            'is_default' => true,
        ]);

        $this->actingAs($this->user)
            ->post(route('settings.subscription.gateway.activate'), [
                'venue_id' => $this->venue->id,
            ])
            ->assertRedirect();

        $venueSubscription = $this->venue->subscription;

        $this->assertNotNull($venueSubscription);
        $this->assertTrue($venueSubscription->isBilledByGateway());
        $this->assertNotNull($venueSubscription->gateway_customer_id);
    }

    public function test_owner_can_activate_gateway_subscription_for_unified_corporation(): void
    {
        $this->venue->corporation->subscriptions()->latest('started_at')->first()
            ->update(['billing_mode' => BillingMode::Unified]);

        UserPaymentMethod::factory()->create([
            'user_id' => $this->user->id,
            'gateway_token' => 'fake_card_token',
            'is_default' => true,
        ]);

        $this->actingAs($this->user)
            ->post(route('settings.subscription.gateway.activate'))
            ->assertRedirect();

        $subscription = $this->venue->corporation->subscriptions()->latest('started_at')->first();

        $this->assertTrue($subscription->isBilledByGateway());
    }

    public function test_owner_cannot_activate_gateway_subscription_without_payment_method(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.subscription.gateway.activate'), [
                'venue_id' => $this->venue->id,
            ])
            ->assertRedirect();

        $this->assertFalse($this->venue->subscription->fresh()->isBilledByGateway());
    }

    public function test_owner_cannot_activate_gateway_subscription_for_venue_of_another_corporation(): void
    {
        $otherVenue = Venue::factory()->create();

        UserPaymentMethod::factory()->create([
            'user_id' => $this->user->id,
            'gateway_token' => 'fake_card_token',
            'is_default' => true,
        ]);

        $this->actingAs($this->user)
            ->post(route('settings.subscription.gateway.activate'), [
                'venue_id' => $otherVenue->id,
            ])
            ->assertForbidden();
    }
}
