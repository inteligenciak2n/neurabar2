<?php

namespace Tests\Feature\Subscription;

use App\Actions\Subscription\ApproveVenuePlanChangeRequestAction;
use App\Enums\VenuePlanChangeStatus;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\PlanCatalogVersion;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenuePlanAssignment;
use App\Models\Tenant\VenuePlanChangeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApproveVenuePlanChangeRequestActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:migrate-all --fresh --force');
    }

    public function test_approval_closes_the_current_assignment_and_schedules_the_requested_plan_without_overlap(): void
    {
        $venue = Venue::factory()->create();
        $reviewer = User::factory()->create();
        $currentPlan = PlanCatalog::factory()->create();
        $currentVersion = PlanCatalogVersion::factory()->create([
            'plan_catalog_id' => $currentPlan->id,
            'effective_from' => '2026-01-01',
        ]);
        $currentAssignment = VenuePlanAssignment::factory()->create([
            'venue_id' => $venue->id,
            'plan_catalog_id' => $currentPlan->id,
            'plan_catalog_version_id' => $currentVersion->id,
            'starts_on' => '2026-01-01',
        ]);
        $requestedPlan = PlanCatalog::factory()->create();
        $requestedVersion = PlanCatalogVersion::factory()->create([
            'plan_catalog_id' => $requestedPlan->id,
            'effective_from' => '2026-01-01',
        ]);
        $changeRequest = VenuePlanChangeRequest::factory()->create([
            'venue_id' => $venue->id,
            'requested_plan_catalog_id' => $requestedPlan->id,
            'requested_plan_catalog_version_id' => $requestedVersion->id,
            'effective_on' => '2026-08-01',
        ]);

        $assignment = app(ApproveVenuePlanChangeRequestAction::class)->execute($changeRequest, $reviewer, 'Approved for next cycle.');

        $this->assertSame('2026-08-01', $assignment->starts_on->toDateString());
        $this->assertNull($assignment->ends_on);
        $this->assertSame('2026-07-31', $currentAssignment->fresh()->ends_on->toDateString());
        $this->assertSame(VenuePlanChangeStatus::Approved, $changeRequest->fresh()->status);
        $this->assertSame($assignment->id, $changeRequest->fresh()->approved_assignment_id);
        $this->assertCount(1, VenuePlanAssignment::query()
            ->where('venue_id', $venue->id)
            ->whereDate('starts_on', '<=', '2026-08-01')
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', '2026-08-01'))
            ->get());
    }

    public function test_approval_rejects_a_plan_version_outside_its_effective_period(): void
    {
        $venue = Venue::factory()->create();
        $currentAssignment = VenuePlanAssignment::factory()->create([
            'venue_id' => $venue->id,
            'starts_on' => '2026-01-01',
        ]);
        $requestedPlan = PlanCatalog::factory()->create();
        $requestedVersion = PlanCatalogVersion::factory()->create([
            'plan_catalog_id' => $requestedPlan->id,
            'effective_from' => '2026-09-01',
        ]);
        $changeRequest = VenuePlanChangeRequest::factory()->create([
            'venue_id' => $venue->id,
            'requested_plan_catalog_id' => $requestedPlan->id,
            'requested_plan_catalog_version_id' => $requestedVersion->id,
            'effective_on' => '2026-08-01',
        ]);

        try {
            app(ApproveVenuePlanChangeRequestAction::class)->execute($changeRequest, User::factory()->create());
            $this->fail('Expected the invalid plan version to be rejected.');
        } catch (ValidationException) {
            $this->assertNull($currentAssignment->fresh()->ends_on);
            $this->assertSame(VenuePlanChangeStatus::Pending, $changeRequest->fresh()->status);
        }
    }

    public function test_approval_rejects_a_venue_with_an_existing_future_assignment(): void
    {
        $venue = Venue::factory()->create();
        VenuePlanAssignment::factory()->create([
            'venue_id' => $venue->id,
            'starts_on' => '2026-01-01',
        ]);
        VenuePlanAssignment::factory()->create([
            'venue_id' => $venue->id,
            'starts_on' => '2026-09-01',
        ]);
        $requestedPlan = PlanCatalog::factory()->create();
        $requestedVersion = PlanCatalogVersion::factory()->create([
            'plan_catalog_id' => $requestedPlan->id,
            'effective_from' => '2026-01-01',
        ]);
        $changeRequest = VenuePlanChangeRequest::factory()->create([
            'venue_id' => $venue->id,
            'requested_plan_catalog_id' => $requestedPlan->id,
            'requested_plan_catalog_version_id' => $requestedVersion->id,
            'effective_on' => '2026-08-01',
        ]);

        $this->expectException(ValidationException::class);

        app(ApproveVenuePlanChangeRequestAction::class)->execute($changeRequest, User::factory()->create());
    }
}
