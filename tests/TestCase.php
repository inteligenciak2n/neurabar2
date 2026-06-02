<?php

namespace Tests;

use App\Enums\ProfileEnum;
use App\Enums\UserRole;
use App\Models\Tenant\Venue;
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

        $user = User::factory()->create([
            'current_venue_id' => $venue->id,
        ]);

        $venue->users()->attach($user->id, ['role' => $role->value]);

        $this->actingAs($user);

        app()->instance('tenant', $venue);
        app()->instance('operational_connection', 'operation_default_1');

        return $user;
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
