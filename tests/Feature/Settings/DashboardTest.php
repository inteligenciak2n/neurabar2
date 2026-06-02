<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\Orders\Attendance;
use App\Models\Payment\Payment;
use App\Models\Tenant\Venue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_dashboard_renders_correct_component(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('Dashboard')
                    ->has('open_attendances_count')
                    ->has('items_in_preparation')
                    ->has('todays_revenue')
                    ->has('attendances_list')
                    ->has('stations_summary')
            );
    }

    public function test_open_attendances_count_returns_only_current_tenant(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        Attendance::factory()->open()->create(['venue_id' => $venue->id]);

        // Another venue
        $otherVenue = Venue::factory()->create();
        Attendance::factory()->open()->create(['venue_id' => $otherVenue->id]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->where('open_attendances_count', 2)
            );
    }

    public function test_todays_revenue_does_not_include_old_payments(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $todayAttendance = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        Payment::factory()->create([
            'attendance_id' => $todayAttendance->id,
            'grand_total' => 100.00,
            'created_at' => now(),
        ]);

        $oldAttendance = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        Payment::factory()->create([
            'attendance_id' => $oldAttendance->id,
            'grand_total' => 200.00,
            'created_at' => now()->subDays(2),
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->where('todays_revenue', fn ($v) => $v == 100)
            );
    }

    public function test_todays_revenue_does_not_include_other_tenant_payments(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $attendance = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        Payment::factory()->create([
            'attendance_id' => $attendance->id,
            'grand_total' => 50.00,
            'created_at' => now(),
        ]);

        $otherVenue = Venue::factory()->create();
        $otherAttendance = Attendance::factory()->closed()->create(['venue_id' => $otherVenue->id]);
        Payment::factory()->create([
            'attendance_id' => $otherAttendance->id,
            'grand_total' => 500.00,
            'created_at' => now(),
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->where('todays_revenue', fn ($v) => $v == 50)
            );
    }

    public function test_attendances_list_shows_only_open_attendances(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        Attendance::factory()->open()->create(['venue_id' => $venue->id]);
        Attendance::factory()->closed()->create(['venue_id' => $venue->id]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->count('attendances_list', 1)
            );
    }
}
