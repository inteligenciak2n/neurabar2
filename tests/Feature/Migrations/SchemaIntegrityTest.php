<?php

namespace Tests\Feature\Migrations;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class SchemaIntegrityTest extends TestCase
{
    use RefreshAllDatabases;

    /**
     * @return array<string, list<string>>
     */
    public static function tableColumnsProvider(): array
    {
        return [
            'venues' => ['venues', ['id', 'corporation_id', 'name', 'active', 'timezone']],
            'corporations' => ['corporations', ['id', 'owner_id', 'name', 'tax_id', 'plan_catalog_id', 'active', 'self_connection']],
            'user_venue' => ['user_venue', ['user_id', 'venue_id', 'role']],
            'venue_invitations' => ['venue_invitations', ['id', 'email', 'venue_id', 'role', 'token', 'expires_at']],
            'plan_catalogs' => ['plan_catalogs', ['id', 'code', 'name', 'monthly_price', 'active']],
            'venue_settings' => ['venue_settings', ['id', 'venue_id', 'cover_charge', 'service_fee_percent']],
            'kitchen_stations' => ['kitchen_stations', ['id', 'venue_id', 'name', 'sort_order', 'active']],
            'preparation_statuses' => ['preparation_statuses', ['id', 'venue_id', 'name', 'color', 'sort_order']],
            'service_locations' => ['service_locations', ['id', 'venue_id', 'name', 'type', 'active']],
            'menus' => ['menus', ['id', 'venue_id', 'name', 'active']],
            'menu_categories' => ['menu_categories', ['id', 'menu_id', 'name', 'sort_order']],
            'products' => ['products', ['id', 'category_id', 'name', 'price', 'active']],
            'modifier_groups' => ['modifier_groups', ['id', 'venue_id', 'name', 'required']],
            'modifier_options' => ['modifier_options', ['id', 'modifier_group_id', 'name', 'extra_price']],
            'combos' => ['combos', ['id', 'venue_id', 'name', 'price', 'active']],
            'combo_items' => ['combo_items', ['id', 'combo_id', 'product_id', 'quantity']],
            'attendances' => ['attendances', ['id', 'venue_id', 'status', 'attendance_channel_id']],
            'orders' => ['orders', ['id', 'attendance_id', 'order_number', 'status']],
            'order_items' => ['order_items', ['id', 'order_id', 'product_id', 'quantity', 'unit_price']],
            'order_item_modifiers' => ['order_item_modifiers', ['id', 'order_item_id', 'modifier_option_id']],
            'payments' => ['payments', ['id', 'attendance_id', 'grand_total']],
            'payment_items' => ['payment_items', ['id', 'payment_id', 'method', 'amount']],
        ];
    }

    /**
     * @param  list<string>  $columns
     */
    #[DataProvider('tableColumnsProvider')]
    public function test_table_has_expected_columns(string $table, array $columns): void
    {
        $connection = $this->resolveConnectionForTable($table) ?? 'saas';
        $schema = Schema::connection($connection);

        $this->assertTrue($schema->hasTable($table), "Table [{$table}] does not exist.");

        foreach ($columns as $column) {
            $this->assertTrue(
                $schema->hasColumn($table, $column),
                "Table [{$table}] is missing column [{$column}]."
            );
        }
    }
}
