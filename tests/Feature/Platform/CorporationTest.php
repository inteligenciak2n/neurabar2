<?php

namespace Tests\Feature\Platform;

use App\Enums\ProfileEnum;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\PlanCatalog;
use Illuminate\Support\Facades\Mail;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class CorporationTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_backoffice_user_can_list_corporations(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::Finance);

        Corporation::factory()->count(3)->create();

        $this->get(route('platform.corporations.index'))->assertOk();
    }

    public function test_backoffice_user_can_create_corporation(): void
    {
        Mail::fake();

        $this->loginAsPlatformUser(ProfileEnum::Registration);

        $this->post(route('platform.corporations.store'), [
            'name' => 'Test Corp',
            'email' => 'corp@test.com',
            'timezone' => 'America/Sao_Paulo',
            'owner_name' => 'John Owner',
            'owner_email' => 'john@test.com',
        ])->assertRedirect(route('platform.corporations.index'));

        $this->assertDatabaseHas('corporations', ['name' => 'Test Corp']);
        $this->assertDatabaseHas('users', ['email' => 'john@test.com']);
    }

    public function test_backoffice_user_can_assign_plan(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::Registration);

        $corporation = Corporation::factory()->create();
        $plan = PlanCatalog::factory()->create(['monthly_price' => 299.00]);

        $this->put(route('platform.corporations.plan', $corporation->id), [
            'plan_catalog_id' => $plan->id,
            'subscription_value' => 299.00,
            'billing_mode' => 'per_venue',
            'billing_day' => 10,
            'grace_period_days' => 5,
            'started_at' => today()->toDateString(),
            'trial_ends_at' => today()->addDays(14)->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('corporation_subscriptions', [
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => $plan->id,
            'billing_mode' => 'per_venue',
        ]);
    }

    public function test_backoffice_user_can_search_corporations(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::ReadOnly);

        Corporation::factory()->create(['name' => 'Acme Corp']);
        Corporation::factory()->create(['name' => 'Other Co']);

        $response = $this->get(route('platform.corporations.index', ['search' => 'Acme']));
        $response->assertOk();

        $corporations = $response->original->getData()['page']['props']['corporations']['data'];
        $this->assertCount(1, $corporations);
        $this->assertEquals('Acme Corp', $corporations[0]['name']);
    }
}
