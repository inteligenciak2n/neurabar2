<?php

namespace Tests;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\ProfileEnum;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenueSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @var bool Garante que db:migrate-all só roda uma vez por processo */
    private static bool $databaseMigrated = false;

    /**
     * Sobrescreve migrateDatabases() do trait RefreshDatabase para rodar
     * db:migrate-all (todos os bancos) em vez de migrate:fresh (conexão padrão).
     * O flag estático garante que rode apenas uma vez por processo PHPUnit.
     */
    protected function migrateDatabases()
    {
        if (! self::$databaseMigrated) {
            $this->artisan('db:migrate-all --fresh --force');
            self::$databaseMigrated = true;
        }
    }

    /**
     * Login as a venue user with the given role, optionally tied to a specific venue.
     */
    protected function loginAs(UserRole $role, ?Venue $venue = null): User
    {
        $venue ??= Venue::factory()->create();

        $this->ensureVenueHasSubscriptionAndMenu($venue);

        $user = User::factory()->create([
            'current_venue_id' => $venue->id,
        ]);

        $venue->users()->attach($user->id, ['role' => $role->value]);

        $this->actingAs($user);

        app()->instance('tenant', $venue);
        app()->instance('operational_connection', 'operation_default_1');

        return $user;
    }

    protected function ensureVenueHasSubscriptionAndMenu(Venue $venue): void
    {
        if (! $venue->corporation->subscription) {
            $plan = PlanCatalog::factory()->create();

            $subscription = CorporationSubscription::factory()->create([
                'corporation_id' => $venue->corporation_id,
                'plan_catalog_id' => $plan->id,
                'status' => SubscriptionStatus::Active,
            ]);

            VenueSubscription::factory()->create([
                'venue_id' => $venue->id,
                'corporation_subscription_id' => $subscription->id,
                'plan_catalog_id' => $plan->id,
                'status' => SubscriptionStatus::Active,
            ]);
        }

        if (! $venue->corporation->modules()->where('module_code', ModuleCode::Menu->value)->exists()) {
            CorporationModule::factory()->create([
                'corporation_id' => $venue->corporation_id,
                'module_code' => ModuleCode::Menu->value,
                'status' => ModuleStatus::Active,
            ]);
        }

        if (! $venue->modules()->where('module_code', ModuleCode::Menu->value)->exists()) {
            VenueModule::factory()->create([
                'venue_id' => $venue->id,
                'module_code' => ModuleCode::Menu->value,
                'status' => ModuleStatus::Active,
            ]);
        }
    }

    /**
     * Login as a platform-level user with the given profile.
     */
    protected function loginAsPlatformUser(ProfileEnum $profile): User
    {
        $user = User::factory()->create(['profile' => $profile]);

        $this->actingAs($user);

        return $user;
    }

    /**
     * Tabelas que residem no banco operacional (não no saas).
     *
     * @var list<string>
     */
    private const OPERATIONAL_TABLES = [
        'kitchen_stations',
        'venue_settings',
        'preparation_statuses',
        'service_locations',
        'menus',
        'categories',
        'menu_categories',
        'products',
        'product_variations',
        'combos',
        'combo_products',
        'combo_items',
        'modifier_groups',
        'modifier_options',
        'product_modifier_group',
        'orders',
        'order_items',
        'order_item_modifiers',
        'attendances',
        'attendance_channels',
        'service_requests',
        'payments',
        'payment_items',
    ];

    /** Resolve a conexão correta para a tabela: operacional ou saas. */
    protected function resolveConnectionForTable(mixed $table): ?string
    {
        if (! is_string($table)) {
            return null; // model class — o próprio model conhece a conexão
        }

        return in_array($table, self::OPERATIONAL_TABLES, true)
            ? 'operation_default_1'
            : null;
    }

    public function assertDatabaseHas($table, array $data = [], $connection = null): static
    {
        $connection ??= $this->resolveConnectionForTable($table);

        return parent::assertDatabaseHas($table, $data, $connection);
    }

    public function assertDatabaseMissing($table, array $data = [], $connection = null): static
    {
        $connection ??= $this->resolveConnectionForTable($table);

        return parent::assertDatabaseMissing($table, $data, $connection);
    }

    public function assertDatabaseCount($table, int $count, $connection = null): static
    {
        $connection ??= $this->resolveConnectionForTable($table);

        return parent::assertDatabaseCount($table, $count, $connection);
    }
}
