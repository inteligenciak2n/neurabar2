<?php

namespace App\Models\Tenant;

use App\Enums\VenuePlanChangeStatus;
use App\Models\User;
use Database\Factories\Tenant\VenuePlanChangeRequestFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenuePlanChangeRequest extends Model
{
    /** @use HasFactory<VenuePlanChangeRequestFactory> */
    use HasFactory;

    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'venue_id', 'pending_venue_id', 'requested_plan_catalog_id', 'requested_plan_catalog_version_id',
        'approved_assignment_id', 'requested_by', 'reviewed_by', 'status',
        'effective_on', 'reason', 'review_notes', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => VenuePlanChangeStatus::class,
            'effective_on' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function requestedPlanCatalog(): BelongsTo
    {
        return $this->belongsTo(PlanCatalog::class, 'requested_plan_catalog_id');
    }

    public function requestedPlanCatalogVersion(): BelongsTo
    {
        return $this->belongsTo(PlanCatalogVersion::class, 'requested_plan_catalog_version_id');
    }

    public function approvedAssignment(): BelongsTo
    {
        return $this->belongsTo(VenuePlanAssignment::class, 'approved_assignment_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
