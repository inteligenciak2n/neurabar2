<?php

namespace Tests\Feature\Subscription;

use App\Enums\InvoiceStatus;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\PaymentSaasMethod;
use App\Enums\UserRole;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\UserPaymentMethod;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueModule;
use App\Models\User;
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
        $catalog = ModuleCatalog::factory()->create([
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
        $catalog = ModuleCatalog::factory()->create([
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
}
