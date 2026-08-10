<?php

namespace App\Models\Tenant;

use Database\Factories\Tenant\VenuePlanAssignmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VenuePlanAssignment extends Model
{
    /** @use HasFactory<VenuePlanAssignmentFactory> */
    use HasFactory;

    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'venue_id', 'plan_catalog_id', 'plan_catalog_version_id',
        'starts_on', 'ends_on', 'source',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function planCatalog(): BelongsTo
    {
        return $this->belongsTo(PlanCatalog::class);
    }

    public function planCatalogVersion(): BelongsTo
    {
        return $this->belongsTo(PlanCatalogVersion::class);
    }

    public function usageTierOverrides(): HasMany
    {
        return $this->hasMany(VenueModuleUsageTierOverride::class);
    }
}
