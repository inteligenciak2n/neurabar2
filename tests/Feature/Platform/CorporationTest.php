<?php

namespace Tests\Feature\Platform;

use App\Enums\ProfileEnum;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\PlanCatalog;
use Database\Seeders\PlanCatalogsSeeder;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class CorporationTest extends TestCase
{
    use RefreshAllDatabases;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanCatalogsSeeder::class);
    }

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

    public function test_super_admin_can_assign_plan(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $corporation = Corporation::factory()->create();
        $plan = PlanCatalog::factory()->create(['monthly_price' => 29900]);

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

    public function test_corporation_edit_defers_the_audit_data(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $corporation = Corporation::factory()->create();

        $this->get(route('platform.corporations.edit', $corporation->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Corporations/Edit')
                ->has('corporation')
                ->missing('statusHistory')
                ->missing('auditLogs')
            );

        $partial = $this->get(route('platform.corporations.edit', $corporation->id), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) Inertia::getVersion(),
            'X-Inertia-Partial-Component' => 'Platform/Corporations/Edit',
            'X-Inertia-Partial-Data' => 'statusHistory,auditLogs',
        ])->assertOk();

        $props = $partial->json('props');

        $this->assertArrayHasKey('statusHistory', $props);
        $this->assertArrayHasKey('auditLogs', $props);
    }
}
