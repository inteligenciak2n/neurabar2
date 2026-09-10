<?php

namespace Tests\Feature\Production;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\UserRole;
use App\Models\Menu\Product;
use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\Payment\Payment;
use App\Models\Settings\KitchenStation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ProductionDashboardTest extends TestCase
{
    use RefreshAllDatabases;

    private function activateProductionDashboard(Venue $venue): void
    {
        CorporationModule::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'module_code' => ModuleCode::ProductionDashboard->value,
            'status' => ModuleStatus::Active,
        ]);

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::ProductionDashboard->value,
            'status' => ModuleStatus::Active,
        ]);
    }

    public function test_owner_can_view_production_dashboard(): void
    {
        $venue = Venue::factory()->create();
        $this->activateProductionDashboard($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $this->get(route('production.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Production/Index'));
    }

    public function test_section_manager_can_view_production_dashboard(): void
    {
        $venue = Venue::factory()->create();
        $this->activateProductionDashboard($venue);
        $this->loginAs(UserRole::SectionManager, $venue);

        $this->get(route('production.index'))->assertOk();
    }

    public function test_attendant_cannot_view_production_dashboard(): void
    {
        $venue = Venue::factory()->create();
        $this->activateProductionDashboard($venue);
        $this->loginAs(UserRole::Attendant, $venue);

        $this->get(route('production.index'))->assertForbidden();
    }

    public function test_blocks_access_when_production_dashboard_module_is_not_active(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $this->get(route('production.index'))->assertForbidden();
    }

    public function test_top_items_are_ranked_by_quantity_sold(): void
    {
        $venue = Venue::factory()->create();
        $this->activateProductionDashboard($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $attendance = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);

        $bestSeller = Product::factory()->create(['name' => 'Chopp']);
        $runnerUp = Product::factory()->create(['name' => 'Coxinha']);

        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $bestSeller->id, 'quantity' => 10, 'unit_price' => 5.00]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $runnerUp->id, 'quantity' => 3, 'unit_price' => 8.00]);

        $this->get(route('production.index', ['period' => 'today']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('metrics.top_items.0.name', 'Chopp')
                ->where('metrics.top_items.0.quantity', 10)
                ->where('metrics.top_items.0.revenue', fn ($v) => $v == 50.0)
            );
    }

    public function test_peak_hours_counts_orders_created_per_hour(): void
    {
        $venue = Venue::factory()->create();
        $this->activateProductionDashboard($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $attendance = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        Order::factory()->create(['attendance_id' => $attendance->id, 'created_at' => today()->setTime(10, 0)]);
        Order::factory()->create(['attendance_id' => $attendance->id, 'created_at' => today()->setTime(10, 30)]);
        Order::factory()->create(['attendance_id' => $attendance->id, 'created_at' => today()->setTime(19, 0)]);

        $this->get(route('production.index', ['period' => 'today']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('metrics.peak_hours', function ($hours) {
                $hour10 = collect($hours)->firstWhere('hour', 10);
                $hour19 = collect($hours)->firstWhere('hour', 19);

                return $hour10['orders_count'] === 2 && $hour19['orders_count'] === 1 && count($hours) === 24;
            }));
    }

    public function test_station_speed_computes_average_preparation_minutes(): void
    {
        $venue = Venue::factory()->create();
        $this->activateProductionDashboard($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $station = KitchenStation::factory()->create(['venue_id' => $venue->id, 'name' => 'Cozinha']);
        $product = Product::factory()->create(['kitchen_station_id' => $station->id]);

        $attendance = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        $order = Order::factory()->create(['attendance_id' => $attendance->id]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'created_at' => now()->subMinutes(10),
            'ready_at' => now(),
        ]);

        $this->get(route('production.index', ['period' => 'today']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('metrics.station_speed.0.name', 'Cozinha')
                ->where('metrics.station_speed.0.avg_minutes', fn ($value) => $value >= 9.5 && $value <= 10.5)
            );
    }

    public function test_top_attendants_ranking_excludes_self_order_attendances(): void
    {
        $venue = Venue::factory()->create();
        $this->activateProductionDashboard($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $attendant = User::factory()->create(['name' => 'João']);

        $attendanceWithStaff = Attendance::factory()->closed()->create([
            'venue_id' => $venue->id,
            'created_by' => $attendant->id,
        ]);
        Payment::factory()->create(['attendance_id' => $attendanceWithStaff->id, 'grand_total' => 120.00, 'created_at' => now()]);

        // Self-order (sem atendente) — não deve entrar no ranking
        Attendance::factory()->closed()->create(['venue_id' => $venue->id, 'created_by' => null]);

        $this->get(route('production.index', ['period' => 'today']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('metrics.top_attendants', fn ($list) => count($list) === 1)
                ->where('metrics.top_attendants.0.name', 'João')
                ->where('metrics.top_attendants.0.attendances_count', 1)
                ->where('metrics.top_attendants.0.revenue', fn ($v) => $v == 120.0)
            );
    }
}
