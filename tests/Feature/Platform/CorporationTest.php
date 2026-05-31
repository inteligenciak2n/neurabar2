<?php

namespace Tests\Feature\Platform;

use App\Enums\UserRole;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\PlanCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CorporationTest extends TestCase
{
    use RefreshDatabase;

    public function test_backoffice_user_can_list_corporations(): void
    {
        $this->loginAsPlatformUser(UserRole::Finance);

        Corporation::factory()->count(3)->create();

        $this->get(route('platform.corporations.index'))->assertOk();
    }

    public function test_backoffice_user_can_create_corporation(): void
    {
        Mail::fake();

        $this->loginAsPlatformUser(UserRole::Registration);

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
        $this->loginAsPlatformUser(UserRole::Registration);

        $corporation = Corporation::factory()->create();
        $plan = PlanCatalog::factory()->create(['monthly_price' => 299.00]);

        $this->put(route('platform.corporations.plan', $corporation->id), [
            'plan_catalog_id' => $plan->id,
            'subscription_value' => 299.00,
            'plan_start_date' => today()->toDateString(),
            'plan_end_date' => today()->addYear()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('corporations', [
            'id' => $corporation->id,
            'plan_catalog_id' => $plan->id,
        ]);
    }

    public function test_backoffice_user_can_search_corporations(): void
    {
        $this->loginAsPlatformUser(UserRole::ReadOnly);

        Corporation::factory()->create(['name' => 'Acme Corp']);
        Corporation::factory()->create(['name' => 'Other Co']);

        $response = $this->get(route('platform.corporations.index', ['search' => 'Acme']));
        $response->assertOk();

        $corporations = $response->original->getData()['page']['props']['corporations']['data'];
        $this->assertCount(1, $corporations);
        $this->assertEquals('Acme Corp', $corporations[0]['name']);
    }
}
