<?php

namespace Tests\Feature\Platform;

use App\Enums\BillingMode;
use App\Enums\ProfileEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\PlanCatalog;
use Database\Seeders\PlanCatalogsSeeder;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class CorporationSubscriptionUpdateTest extends TestCase
{
    use RefreshAllDatabases;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanCatalogsSeeder::class);
    }

    public function test_super_admin_can_update_subscription_settings(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $corporation = Corporation::factory()->create();
        PlanCatalog::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => PlanCatalog::first()->id,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Trial,
            'billing_day' => 1,
        ]);

        $this->put(route('platform.corporations.subscription.update', $corporation->id), [
            'billing_mode' => 'unified',
            'status' => 'active',
            'billing_day' => 15,
            'grace_period_days' => 5,
            'started_at' => today()->toDateString(),
            'trial_ends_at' => today()->addDays(7)->toDateString(),
            'ended_at' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('corporation_subscriptions', [
            'corporation_id' => $corporation->id,
            'billing_mode' => BillingMode::Unified->value,
            'status' => SubscriptionStatus::Active->value,
            'billing_day' => 15,
            'grace_period_days' => 5,
        ]);
    }

    public function test_registration_profile_cannot_update_subscription(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::Registration);

        $corporation = Corporation::factory()->create();

        $this->put(route('platform.corporations.subscription.update', $corporation->id), [
            'billing_mode' => 'unified',
            'status' => 'active',
            'billing_day' => 15,
            'grace_period_days' => 5,
            'started_at' => today()->toDateString(),
        ])->assertForbidden();
    }
}
