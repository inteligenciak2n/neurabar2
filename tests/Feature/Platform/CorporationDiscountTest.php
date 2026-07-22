<?php

namespace Tests\Feature\Platform;

use App\Enums\ProfileEnum;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\PlanCatalog;
use Database\Seeders\PlanCatalogsSeeder;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class CorporationDiscountTest extends TestCase
{
    use RefreshAllDatabases;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanCatalogsSeeder::class);
    }

    public function test_super_admin_can_create_discount(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $corporation = Corporation::factory()->create();
        PlanCatalog::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => PlanCatalog::first()->id,
        ]);

        $this->post(route('platform.corporations.discounts.store', $corporation->id), [
            'type' => 'percentage',
            'value' => 15,
            'description' => 'Launch discount',
            'valid_from' => today()->toDateString(),
            'valid_until' => today()->addMonths(6)->toDateString(),
            'max_months' => 6,
        ])->assertRedirect();

        $this->assertDatabaseHas('corporation_discounts', [
            'corporation_id' => $corporation->id,
            'type' => 'percentage',
            'value' => 15,
            'max_months' => 6,
        ]);
    }

    public function test_registration_profile_cannot_create_discount(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::Registration);

        $corporation = Corporation::factory()->create();

        $this->post(route('platform.corporations.discounts.store', $corporation->id), [
            'type' => 'fixed',
            'value' => 50,
            'valid_from' => today()->toDateString(),
        ])->assertForbidden();
    }

    public function test_super_admin_can_delete_discount(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $corporation = Corporation::factory()->create();
        $discount = $corporation->discounts()->create([
            'type' => 'fixed',
            'value' => 30,
            'valid_from' => today()->toDateString(),
        ]);

        $this->delete(route('platform.corporations.discounts.destroy', [$corporation->id, $discount->id]))
            ->assertRedirect();

        $this->assertSoftDeleted('corporation_discounts', ['id' => $discount->id]);
    }
}

