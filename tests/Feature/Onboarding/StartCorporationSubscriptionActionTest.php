<?php

namespace Tests\Feature\Onboarding;

use App\Actions\Onboarding\StartCorporationSubscriptionAction;
use App\Enums\ModuleCode;
use App\Models\Tenant\PlanCatalog;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class StartCorporationSubscriptionActionTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_it_assigns_the_default_plan_to_the_new_subscription(): void
    {
        $plan = PlanCatalog::factory()->create([
            'code' => 'onboarding-default',
            'monthly_price' => 19900,
            'active' => true,
        ]);

        config(['billing.default_plan_code' => 'onboarding-default']);

        $user = User::factory()->create();

        $corporation = (new StartCorporationSubscriptionAction)->execute($user, [ModuleCode::Kds->value], 1);

        $this->assertDatabaseHas('corporation_subscriptions', [
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => $plan->id,
        ]);
    }

    public function test_it_ignores_inactive_default_plan(): void
    {
        PlanCatalog::factory()->create([
            'code' => 'onboarding-default',
            'active' => false,
        ]);

        config(['billing.default_plan_code' => 'onboarding-default']);

        $user = User::factory()->create();

        $corporation = (new StartCorporationSubscriptionAction)->execute($user, [], 1);

        $this->assertDatabaseHas('corporation_subscriptions', [
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => null,
        ]);
    }
}
