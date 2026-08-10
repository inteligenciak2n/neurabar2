<?php

namespace App\Models\Tenant;

use Database\Factories\Tenant\PlanCatalogVersionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanCatalogVersion extends Model
{
    /** @use HasFactory<PlanCatalogVersionFactory> */
    use HasFactory;

    use HasUuids;

    protected $connection = 'saas';

    protected $fillable = [
        'plan_catalog_id', 'version', 'status', 'effective_from', 'effective_until',
        'minimum_monthly_price', 'infrastructure_type', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'minimum_monthly_price' => 'integer',
        ];
    }

    public function planCatalog(): BelongsTo
    {
        return $this->belongsTo(PlanCatalog::class);
    }

    public function usageTiers(): HasMany
    {
        return $this->hasMany(PlanModuleUsageTier::class);
    }

    public function venueAssignments(): HasMany
    {
        return $this->hasMany(VenuePlanAssignment::class);
    }
}
