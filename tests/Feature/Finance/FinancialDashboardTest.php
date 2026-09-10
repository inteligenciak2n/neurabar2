<?php

namespace Tests\Feature\Finance;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Orders\Attendance;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentItem;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class FinancialDashboardTest extends TestCase
{
    use RefreshAllDatabases;

    private function activateFinancialDashboard(Venue $venue): void
    {
        CorporationModule::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'module_code' => ModuleCode::FinancialDashboard->value,
            'status' => ModuleStatus::Active,
        ]);

        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::FinancialDashboard->value,
            'status' => ModuleStatus::Active,
        ]);
    }

    public function test_owner_can_view_financial_dashboard(): void
    {
        $venue = Venue::factory()->create();
        $this->activateFinancialDashboard($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $this->get(route('finance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Finance/Index'));
    }

    public function test_attendant_cannot_view_financial_dashboard(): void
    {
        $venue = Venue::factory()->create();
        $this->activateFinancialDashboard($venue);
        $this->loginAs(UserRole::Attendant, $venue);

        $this->get(route('finance.index'))->assertForbidden();
    }

    public function test_blocks_access_when_financial_dashboard_module_is_not_active(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $this->get(route('finance.index'))->assertForbidden();
    }

    public function test_rejects_a_custom_range_wider_than_366_days(): void
    {
        $venue = Venue::factory()->create();
        $this->activateFinancialDashboard($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $this->get(route('finance.index', ['period' => 'custom', 'from' => '1900-01-01', 'to' => '2100-01-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_returns_gross_revenue_average_ticket_and_attendances_count(): void
    {
        $venue = Venue::factory()->create();
        $this->activateFinancialDashboard($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $attendanceA = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        Payment::factory()->create(['attendance_id' => $attendanceA->id, 'grand_total' => 100.00, 'created_at' => now()]);

        $attendanceB = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        Payment::factory()->create(['attendance_id' => $attendanceB->id, 'grand_total' => 50.00, 'created_at' => now()]);

        // Fora do período (não deve entrar no cálculo)
        $oldAttendance = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        Payment::factory()->create(['attendance_id' => $oldAttendance->id, 'grand_total' => 999.00, 'created_at' => now()->subDays(5)]);

        $this->get(route('finance.index', ['period' => 'today']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('metrics.gross_revenue', fn ($v) => $v == 150.0)
                ->where('metrics.average_ticket', fn ($v) => $v == 75.0)
                ->where('metrics.attendances_count', 2)
            );
    }

    public function test_previous_period_comparison_percentage(): void
    {
        $venue = Venue::factory()->create();
        $this->activateFinancialDashboard($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $todayAttendance = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        Payment::factory()->create(['attendance_id' => $todayAttendance->id, 'grand_total' => 200.00, 'created_at' => now()]);

        $yesterdayAttendance = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        Payment::factory()->create(['attendance_id' => $yesterdayAttendance->id, 'grand_total' => 100.00, 'created_at' => now()->subDay()]);

        $this->get(route('finance.index', ['period' => 'today']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('metrics.previous_period.gross_revenue', fn ($v) => $v == 100.0));
    }

    public function test_payment_method_breakdown(): void
    {
        $venue = Venue::factory()->create();
        $this->activateFinancialDashboard($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $attendance = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        $payment = Payment::factory()->create(['attendance_id' => $attendance->id, 'grand_total' => 150.00, 'created_at' => now()]);
        PaymentItem::factory()->create(['payment_id' => $payment->id, 'method' => PaymentMethod::Pix->value, 'amount' => 100.00]);
        PaymentItem::factory()->create(['payment_id' => $payment->id, 'method' => PaymentMethod::Cash->value, 'amount' => 50.00]);

        $this->get(route('finance.index', ['period' => 'today']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('metrics.payment_method_breakdown', function ($breakdown) {
                $pix = collect($breakdown)->firstWhere('method', 'pix');
                $cash = collect($breakdown)->firstWhere('method', 'cash');

                return $pix['total'] == 100.0 && $cash['total'] == 50.0;
            }));
    }

    public function test_scope_forces_venue_when_corporation_has_single_venue(): void
    {
        $venue = Venue::factory()->create();
        $this->activateFinancialDashboard($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $this->get(route('finance.index', ['period' => 'today', 'scope' => 'corporation']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('canViewCorporation', false)
                ->where('filters.scope', 'venue')
            );
    }

    public function test_corporation_scope_returns_breakdown_by_venue(): void
    {
        $venueA = Venue::factory()->create();
        $this->activateFinancialDashboard($venueA);
        $this->loginAs(UserRole::Owner, $venueA);

        $venueB = Venue::factory()->create(['corporation_id' => $venueA->corporation_id]);

        $attendanceA = Attendance::factory()->closed()->create(['venue_id' => $venueA->id]);
        Payment::factory()->create(['attendance_id' => $attendanceA->id, 'grand_total' => 100.00, 'created_at' => now()]);

        $attendanceB = Attendance::factory()->closed()->create(['venue_id' => $venueB->id]);
        Payment::factory()->create(['attendance_id' => $attendanceB->id, 'grand_total' => 300.00, 'created_at' => now()]);

        $this->get(route('finance.index', ['period' => 'today', 'scope' => 'corporation']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('canViewCorporation', true)
                ->where('metrics.gross_revenue', fn ($v) => $v == 400.0)
                ->where('metrics.venues_breakdown', fn ($breakdown) => collect($breakdown)->count() === 2)
            );
    }

    public function test_does_not_include_other_corporation_payments(): void
    {
        $venue = Venue::factory()->create();
        $this->activateFinancialDashboard($venue);
        $this->loginAs(UserRole::Owner, $venue);

        $attendance = Attendance::factory()->closed()->create(['venue_id' => $venue->id]);
        Payment::factory()->create(['attendance_id' => $attendance->id, 'grand_total' => 50.00, 'created_at' => now()]);

        $otherVenue = Venue::factory()->create();
        $otherAttendance = Attendance::factory()->closed()->create(['venue_id' => $otherVenue->id]);
        Payment::factory()->create(['attendance_id' => $otherAttendance->id, 'grand_total' => 500.00, 'created_at' => now()]);

        $this->get(route('finance.index', ['period' => 'today']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('metrics.gross_revenue', fn ($v) => $v == 50.0));
    }
}
