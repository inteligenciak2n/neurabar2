<?php

namespace Tests\Feature\Models;

use App\Enums\AttendanceStatus;
use App\Models\Orders\Attendance;
use App\Models\Settings\KitchenStation;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_tenant_scope_filters_records_by_venue(): void
    {
        $venueA = Venue::factory()->create();
        $venueB = Venue::factory()->create();

        KitchenStation::factory()->create(['venue_id' => $venueA->id, 'name' => 'Station A']);
        KitchenStation::factory()->create(['venue_id' => $venueB->id, 'name' => 'Station B']);

        app()->instance('tenant', $venueA);

        $stations = KitchenStation::all();

        $this->assertCount(1, $stations);
        $this->assertEquals('Station A', $stations->first()->name);
    }

    public function test_tenant_scope_returns_all_records_when_no_tenant_bound(): void
    {
        $venueA = Venue::factory()->create();
        $venueB = Venue::factory()->create();

        KitchenStation::factory()->create(['venue_id' => $venueA->id]);
        KitchenStation::factory()->create(['venue_id' => $venueB->id]);

        app()->forgetInstance('tenant');

        $stations = KitchenStation::all();

        $this->assertCount(2, $stations);
    }

    public function test_belongs_to_venue_auto_fills_venue_id_on_create(): void
    {
        $venue = Venue::factory()->create();
        app()->instance('tenant', $venue);

        $station = KitchenStation::create(['name' => 'Auto-filled']);

        $this->assertEquals($venue->id, $station->venue_id);
    }

    public function test_attendance_open_scope_returns_only_open_attendances(): void
    {
        $venue = Venue::factory()->create();

        Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        Attendance::factory()->closed()->create(['venue_id' => $venue->id]);

        app()->instance('tenant', $venue);

        $openAttendances = Attendance::open()->get();

        $this->assertCount(1, $openAttendances);
        $this->assertEquals(AttendanceStatus::Open, $openAttendances->first()->status);
    }
}
